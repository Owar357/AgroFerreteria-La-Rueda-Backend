<?php

namespace App\Http\Controllers;

use App\Http\Requests\Venta\StoreVentaRequest;
use App\Models\DetalleVenta;
use App\Models\Lote;
use App\Models\LoteDetalleVenta;
use App\Models\Presentacion;
use App\Models\Venta;
use App\Services\CajaService;
use App\Services\KardexService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class VentaController extends Controller
{
    /** Tasa de IVA. Los precios de venta ya incluyen IVA. */
    private const TASA_IVA = 0.13;

    /** Diferencia máxima tolerada entre el total del POS y el calculado por el servidor (redondeos). */
    private const TOLERANCIA_BASE = 0.03;

    private const TOLERANCIA_POR_LINEA = 0.005;

    public function index(Request $request)
    {
        try {
            $perPage = min(max((int) $request->input('per_page', 8), 1), 100);
            
            $fechaDesde = $request->input('fecha_desde');
            $fechaHasta = $request->input('fecha_hasta');
            $search = trim($request->input('search', ''));

            
            if (!$fechaDesde && !$fechaHasta) {
                $fechaDesde = Carbon::now()->subMonth()->format('Y-m-d');
                $fechaHasta = Carbon::now()->format('Y-m-d');
            }

          
            if ($fechaDesde && $fechaHasta && $fechaDesde > $fechaHasta) {
                [$fechaDesde, $fechaHasta] = [$fechaHasta, $fechaDesde];
            }

    
            $ventas = Venta::query()
                ->with(['cliente:id,nombre,tipo_persona', 'vendidoPor:id,name'])
                ->select([
                    'id', 'numero_factura', 'total', 'tipo_pago', 'tipo_factura', 
                    'estado', 'cliente_id', 'apertura_venta_id', 'created_at', 'vendido_por'
                ])
                
               
                ->when($request->filled('cliente'), function ($q) use ($request) {
                    $q->where('cliente_id', $request->cliente);
                })
                
                
                ->when(in_array($request->input('estado'), ['PROCESADA', 'ANULADA']), function ($q) use ($request) {
                    $q->where('estado', $request->estado);
                })
                
               
                ->when(in_array($request->input('tipo_pago'), ['EFECTIVO', 'TARJETA', 'TRANSFERENCIA']), function ($q) use ($request) {
                    $q->where('tipo_pago', $request->tipo_pago);
                })
                
               
                ->when($fechaDesde && $fechaHasta, function ($q) use ($fechaDesde, $fechaHasta) {
                    $q->whereBetween('created_at', [
                        Carbon::parse($fechaDesde)->startOfDay(),
                        Carbon::parse($fechaHasta)->endOfDay(),
                    ]);
                })
                
                
                ->when($fechaDesde && !$fechaHasta, function ($q) use ($fechaDesde) {
                    $q->where('created_at', '>=', Carbon::parse($fechaDesde)->startOfDay());
                })
                
               
                ->when(!$fechaDesde && $fechaHasta, function ($q) use ($fechaHasta) {
                    $q->where('created_at', '<=', Carbon::parse($fechaHasta)->endOfDay());
                })
                
                
                ->when(!empty($search), function ($q) use ($search) {
                    $texto = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $search);
                    $q->where(function ($sub) use ($texto) {
                        $sub->where('numero_factura', 'ilike', "%{$texto}%")
                            ->orWhereHas('vendidoPor', fn ($u) => $u->where('name', 'ilike', "%{$texto}%"));
                    });
                })
                
                ->orderByDesc('created_at')
                ->orderByDesc('id')
                ->paginate($perPage);

            return response()->json($ventas);

        } catch (\Exception $e) {
            Log::error('Error al listar ventas: '.$e->getMessage(), ['exception' => $e]);

            return response()->json([
                'status' => 'error',
                'message' => 'Ocurrió un error interno y no se pudieron obtener los registros',
            ], 500);
        }
    }

    public function store(StoreVentaRequest $request, KardexService $kardexService, CajaService $cajaService)
    {
        try {
            $datosVenta = $request->safe()->except(['detalles']);
            $datosVenta['cliente_id'] = $datosVenta['cliente_id'] ?? 1;
            $datosVenta['tipo_factura'] = $datosVenta['tipo_factura'] ?? '01';

            if ($datosVenta['tipo_factura'] === '03' && (int) $datosVenta['cliente_id'] === 1) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Para emitir Comprobante de Crédito Fiscal debe seleccionar un cliente registrado.',
                ], 400);
            }

            if ($datosVenta['tipo_pago'] !== 'EFECTIVO') {
                $datosVenta['efectivo_recibido'] = null;
            }

           
            $datosVenta['cambio'] = null;

            $detallesRequest = $request->validated()['detalles'];

            $venta = DB::transaction(function () use ($detallesRequest, $kardexService, $cajaService, $datosVenta) {

                $turno = $cajaService->turnoActivo(true);

                if (! $turno) {
                    throw new \DomainException('No hay una apertura de venta activa. Aperture su venta en el módulo de caja para poder vender.');
                }

                if ((int) $turno->cajero_id !== (int) auth()->id()) {
                    throw new \DomainException('El turno activo pertenece a otro cajero. Solo ese cajero puede registrar ventas.');
                }

                $venta = Venta::create([
                    ...$datosVenta,
                    'numero_factura' => $this->numeroFactura(),
                    'apertura_venta_id' => $turno->id,
                    'vendido_por' => auth()->id(),
                ]);

                $totalServidor = '0.00';
                $exentoServidor = '0.00';

                foreach ($detallesRequest as $detalles) {

                    $presentacion = Presentacion::with('producto')->findOrFail($detalles['presentacion_id']);
                    $producto = $presentacion->producto;

                    if ($presentacion->precio_venta === null) {
                        throw new \DomainException("La presentación de \"{$producto->nombre}\" no tiene un precio de venta configurado.");
                    }

                    $esGranel = ($producto->tipo_producto === 'GRANEL');
                    $aplicaIva = (bool) $producto->aplica_iva;
                    $factorConversion = (float) ($presentacion->factor_conversion ?? 1);

                    $precioUnitario = (float) $presentacion->precio_venta;

                    $cantidadSolicitada = $esGranel
                        ? bcmul($detalles['cantidad'], $factorConversion, 4)
                        : $detalles['cantidad'];

                    $descuentoTotalAcumulado = 0.00;
                    $lotesConsumidos = [];

                    while ($cantidadSolicitada > 0) {

                        $queryLote = Lote::query()
                            ->where('cantidad_actual', '>', 0)
                            ->where('estado', 'ACTIVO');

                        if ($esGranel) {
                            $queryLote->where('producto_id', $producto->id);
                        } else {
                            $queryLote->where('presentacion_id', $presentacion->id);
                        }

                        $lote = $queryLote
                            ->orderByRaw('fecha_vencimiento ASC NULLS LAST')
                            ->orderBy('created_at', 'ASC')
                            ->lockForUpdate()
                            ->first();

                        if (! $lote) {
                            throw new \DomainException("No hay stock suficiente en los lotes activos para el producto {$producto->nombre}.");
                        }

                        $porcentajeDescLote = (float) ($lote->porcentaje_descuento ?? 0);
                        $descuentoUnitarioDolar = $precioUnitario * ($porcentajeDescLote / 100);

                        if ($lote->cantidad_actual >= $cantidadSolicitada) {

                            $cantidadTomada = $cantidadSolicitada;

                            $cantidadEnPresentacion = $esGranel
                                ? ($cantidadTomada / $factorConversion)
                                : $cantidadTomada;

                            $descuentoTramo = $descuentoUnitarioDolar * $cantidadEnPresentacion;
                            $descuentoTotalAcumulado += $descuentoTramo;

                            $lotesConsumidos[] = [
                                'lote' => $lote,
                                'cantidad_tomada' => $cantidadTomada,
                                'cantidad_presentacion' => $cantidadEnPresentacion,
                                'es_parcial' => false,
                            ];

                            $lote->cantidad_actual = bcsub($lote->cantidad_actual, $cantidadTomada, 3);
                            if ($lote->cantidad_actual == 0) {
                                $lote->estado = 'AGOTADO';
                            }
                            $lote->save();

                            $cantidadSolicitada = 0;

                        } else {

                            $stockEntregado = $lote->cantidad_actual;

                            $cantidadEntregadaEnPresentacion = $esGranel
                                ? ($stockEntregado / $factorConversion)
                                : $stockEntregado;

                            $descuentoTramo = $descuentoUnitarioDolar * $cantidadEntregadaEnPresentacion;
                            $descuentoTotalAcumulado += $descuentoTramo;

                            $lotesConsumidos[] = [
                                'lote' => $lote,
                                'cantidad_tomada' => $stockEntregado,
                                'cantidad_presentacion' => $cantidadEntregadaEnPresentacion,
                                'es_parcial' => true,
                            ];

                            $lote->cantidad_actual = 0;
                            $lote->estado = 'AGOTADO';
                            $lote->update();

                            $cantidadSolicitada = bcsub($cantidadSolicitada, $stockEntregado, 4);
                        }
                    }

                    $descuentoTotalAcumulado = round($descuentoTotalAcumulado, 2);
                    $subtotalFinal = round(((float) $detalles['cantidad'] * $precioUnitario) - $descuentoTotalAcumulado, 2);
                    $ivaLinea = $aplicaIva
                        ? round($subtotalFinal - ($subtotalFinal / (1 + self::TASA_IVA)), 2)
                        : 0.00;

                    $subtotalTexto = number_format($subtotalFinal, 2, '.', '');
                    $totalServidor = bcadd($totalServidor, $subtotalTexto, 2);

                    if (! $aplicaIva) {
                        $exentoServidor = bcadd($exentoServidor, $subtotalTexto, 2);
                    }

                    $detalleVenta = DetalleVenta::create([
                        'venta_id' => $venta->id,
                        'nombre_producto' => $detalles['nombre_producto'],
                        'presentacion' => $detalles['presentacion'],
                        'cantidad' => $detalles['cantidad'],
                        'precio_unitario' => $precioUnitario,
                        'subtotal' => $subtotalFinal,
                        'iva_aplicado' => $ivaLinea,
                        'unidad_base' => $detalles['unidad_base'],
                        'descuento_aplicado' => $descuentoTotalAcumulado,
                    ]);

                    foreach ($lotesConsumidos as $item) {
                        LoteDetalleVenta::create([
                            'detalle_venta_id' => $detalleVenta->id,
                            'lote_id' => $item['lote']->id,
                            'cantidad_tomada' => $item['cantidad_tomada'],
                        ]);

                        $concepto = $item['es_parcial']
                            ? 'Salida parcial por Venta '.$venta->numero_factura
                            : 'Salida por Venta '.$venta->numero_factura;

                        $kardexService->registrarSalida(
                            $presentacion,
                            $item['lote'],
                            (float) $item['cantidad_presentacion'],
                            $venta,
                            $venta->numero_factura,
                            $concepto
                        );
                    }
                }
                $gravadoConIva = bcsub($totalServidor, $exentoServidor, 2);
                $gravado = number_format(round((float) $gravadoConIva / (1 + self::TASA_IVA), 2), 2, '.', '');
                $iva = bcsub($gravadoConIva, $gravado, 2);

                
                $tolerancia = self::TOLERANCIA_BASE + (self::TOLERANCIA_POR_LINEA * count($detallesRequest));

                if (abs((float) $totalServidor - (float) $datosVenta['total']) > $tolerancia) {
                    throw new \DomainException(sprintf(
                        'El total calculado por el sistema ($%s) no coincide con el del POS ($%s). Actualice el POS y vuelva a intentar.',
                        $totalServidor,
                        number_format((float) $datosVenta['total'], 2, '.', '')
                    ));
                }

                $cambio = null;

                if ($datosVenta['tipo_pago'] === 'EFECTIVO') {
                    $recibido = number_format((float) $datosVenta['efectivo_recibido'], 2, '.', '');

                    if (bccomp($recibido, $totalServidor, 2) < 0) {
                        throw new \DomainException(sprintf(
                            'El efectivo recibido ($%s) es menor al total de la venta ($%s).',
                            $recibido,
                            $totalServidor
                        ));
                    }

                    $cambio = bcsub($recibido, $totalServidor, 2);
                }

                $venta->update([
                    'gravado' => $gravado,
                    'exento' => $exentoServidor,
                    'iva' => $iva,
                    'total' => $totalServidor,
                    'cambio' => $cambio,
                ]);

                return $venta;
            });

            return response()->json([
                'status' => 'ok',
                'message' => 'Venta registrada con éxito',
                'id' => $venta->id,
                'num_documento' => $venta->numero_factura,
                'total' => $venta->total,
                'cambio' => $venta->cambio,
            ], 201);

        } catch (\DomainException $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 400);
        } catch (\Throwable $e) {
            Log::error('Error crítico al procesar venta: '.$e->getMessage(), [
                'exception' => $e,
                'usuario_id' => auth()->id(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Ocurrió un error interno al procesar la venta. Por favor intente nuevamente o contacte a soporte.',
            ], 500);
        }

        
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        try {

            $detallesVenta = DetalleVenta::where('venta_id', $id)
                ->get();

            return response()->json([
                'status' => 'ok',
                'data' => $detallesVenta,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'Error',
                'message' => 'Error interno del servidor',
            ], 500);
        }
    }

    public function numeroFactura()
    {
        $numeroFactura = DB::select(
            'SELECT numero_factura FROM ventas
             ORDER BY numero_factura DESC
             LIMIT 1'
        );

        $resultado = ! empty($numeroFactura) ? $numeroFactura[0]->numero_factura : null;

        $secuencia = $resultado ? ((int) substr($resultado, 4) + 1) : 1;

        return 'FAC-'.str_pad($secuencia, 7, '0', STR_PAD_LEFT);
    }
}
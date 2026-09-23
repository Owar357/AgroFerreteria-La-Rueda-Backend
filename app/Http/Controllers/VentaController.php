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

    /**
     * Listado paginado en el servidor.
     *
     * Filtros: cliente, fecha_desde, fecha_hasta, estado, tipo_pago, search
     * (N° de factura o nombre del vendedor), page y per_page (1 a 100).
     * Sin cliente ni fechas devuelve las ventas de hoy.
     */
    public function index(Request $request)
    {
        try {
            $perPage = min(max((int) $request->input('per_page', 8), 1), 100);

            $resultados = Venta::query()
                ->with(['cliente:id,nombre,tipo_persona', 'vendidoPor:id,name'])
                ->select([
                    'id',
                    'numero_factura',
                    'total',
                    'tipo_pago',
                    'tipo_factura',
                    'estado',
                    'cliente_id',
                    'apertura_venta_id',
                    'created_at',
                    'vendido_por',
                ]);

            if ($request->filled('cliente')) {
                $resultados->where('cliente_id', $request->cliente);
            } elseif ($request->filled('fecha_desde') && $request->filled('fecha_hasta')) {
                $resultados->whereBetween('created_at', [
                    Carbon::parse($request->input('fecha_desde'))->startOfDay(),
                    Carbon::parse($request->input('fecha_hasta'))->endOfDay(),
                ]);
            } elseif ($request->filled('fecha_desde')) {
                $resultados->whereDate('created_at', '>=', $request->input('fecha_desde'));
            } else {
                $resultados->whereDate('created_at', today());
            }

            if (in_array($request->input('estado'), ['PROCESADA', 'ANULADA'], true)) {
                $resultados->where('estado', $request->input('estado'));
            }

            if (in_array($request->input('tipo_pago'), ['EFECTIVO', 'TARJETA', 'TRANSFERENCIA'], true)) {
                $resultados->where('tipo_pago', $request->input('tipo_pago'));
            }

            if ($request->filled('search')) {
                // Se escapan los comodines de LIKE para que se busquen como texto literal
                $texto = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], trim($request->input('search')));

                $resultados->where(function ($q) use ($texto) {
                    $q->where('numero_factura', 'ilike', "%{$texto}%")
                        ->orWhereHas('vendidoPor', fn ($u) => $u->where('name', 'ilike', "%{$texto}%"));
                });
            }

            $ventas = $resultados
                ->orderByDesc('created_at')
                ->orderByDesc('id')
                ->paginate($perPage);

            return response()->json($ventas);

        } catch (\Exception $e) {
            Log::error('Error al listar ventas: '.$e->getMessage(), ['exception' => $e]);

            return response()->json([
                'status' => 'error',
                'message' => 'ocurrio un error interno y no se pudo obtener los registros',
            ], 500);
        }
    }

    /**
     * Registra una venta.
     *
     * El servidor NO confía en los importes del POS: el precio sale de la presentación,
     * el descuento de los lotes consumidos y el IVA del producto. Con eso calcula
     * gravado, exento, iva, total y cambio. Si el total del POS difiere más allá del
     * redondeo, la venta se rechaza y la transacción completa (stock y kardex) se revierte.
     */
    public function store(StoreVentaRequest $request, KardexService $kardexService, CajaService $cajaService)
    {
        try {
            $datosVenta = $request->safe()->except(['detalles']);
            $datosVenta['cliente_id'] = $datosVenta['cliente_id'] ?? 1;
            $datosVenta['tipo_factura'] = $datosVenta['tipo_factura'] ?? '01';

            // Crédito Fiscal (03) exige un cliente registrado; el 1 es "Consumidor Final"
            if ($datosVenta['tipo_factura'] === '03' && (int) $datosVenta['cliente_id'] === 1) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Para emitir Comprobante de Crédito Fiscal debe seleccionar un cliente registrado.',
                ], 400);
            }

            if ($datosVenta['tipo_pago'] !== 'EFECTIVO') {
                $datosVenta['efectivo_recibido'] = null;
            }

            // El cambio se calcula al final, con el total real
            $datosVenta['cambio'] = null;

            $detallesRequest = $request->validated()['detalles'];

            $venta = DB::transaction(function () use ($detallesRequest, $kardexService, $cajaService, $datosVenta) {

                // Si el cierre está en curso, la venta espera y luego se rechaza
                $turno = $cajaService->turnoActivo(true);

                if (! $turno) {
                    throw new \DomainException('No hay una apertura de venta activa. Aperture su venta en el módulo de caja para poder vender.');
                }

                if ((int) $turno->cajero_id !== (int) auth()->id()) {
                    throw new \DomainException('El turno activo pertenece a otro cajero. Solo ese cajero puede registrar ventas.');
                }

                // Los importes (gravado, exento, iva, total) son provisionales: se reemplazan al final
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

                    // El precio sale de la base de datos, no del POS
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

                        // --- CÁLCULO DE DESCUENTO POR LOTE ---
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

                            // Calcular descuento de esta porción parcial
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

                    // --- IMPORTES DE LA LÍNEA, CALCULADOS POR EL SERVIDOR ---
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

                    // CREACIÓN DEL DETALLE DE VENTA CON SU DESCUENTO
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

                    // REGISTRO DE LOTES Y KARDEX CON LOS DATOS ACUMULADOS
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

                // --- TOTALES DE LA VENTA ---
                // gravado + iva = monto con IVA de las líneas gravadas; así gravado + exento + iva = total exacto
                $gravadoConIva = bcsub($totalServidor, $exentoServidor, 2);
                $gravado = number_format(round((float) $gravadoConIva / (1 + self::TASA_IVA), 2), 2, '.', '');
                $iva = bcsub($gravadoConIva, $gravado, 2);

                // El total del POS solo se contrasta: si difiere más allá del redondeo, se rechaza y se revierte todo
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

        return 'FAC-'.str_pad($secuencia, 7, '0', STR_PAD_LEFT);
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
             LIMIT 1
            ', );

        $resultado = ! empty($numeroFactura) ? $numeroFactura[0]->numero_factura : null;

        if ($resultado) {
            $secuencia = (int) substr($resultado, 4) + 1;
        } else {
            $secuencia = 1;
        }

        return 'FAC-'.str_pad($secuencia, 7, '0', STR_PAD_LEFT);
    }
}

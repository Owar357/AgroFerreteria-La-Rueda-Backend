<?php

namespace App\Http\Controllers;

use App\Http\Requests\Venta\StoreVentaRequest;
use App\Models\AperturaCaja;
use App\Models\AperturaVenta;
use App\Models\DetalleVenta;
use App\Models\Lote;
use App\Models\LoteDetalleVenta;
use App\Models\Presentacion;
use App\Models\Venta;
use App\Services\KardexService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class VentaController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        try {

            $resultados = Venta::query()
                ->with(['cliente:id,nombre,tipo_persona', 'vendidoPor:id,name'])
                ->select([
                    'id',
                    'numero_factura',
                    'total',
                    'tipo_pago',
                    'estado',
                    'cliente_id',
                    'apertura_venta_id',
                    'created_at',
                    'vendido_por',
                ]);

            if ($request->cliente) {
                $resultados->where('cliente_id', $request->cliente);
            } elseif ($request->input('fecha_desde') && $request->input('fecha_hasta')) {
                $resultados->whereBetween('created_at', [
                    Carbon::parse($request->input('fecha_desde'))->startOfDay(),
                    Carbon::parse($request->input('fecha_hasta'))->endOfDay(),
                ]);
            } elseif ($request->input('fecha_desde')) {

                $resultados->whereDate('created_at', '>=', $request->input('fecha_desde'));
            } else {
                $resultados->whereDate('created_at', today());

            }

            $ventas = $resultados
                ->orderBy('created_at', 'desc')
                ->paginate($request->input('per_page', 8));

            return response()->json($ventas);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'ocurrio un error interno y no se pudo obtener los registros',
            ], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreVentaRequest $request, KardexService $kardexService)
    {
        try {
            $cajaGeneralAbierta = AperturaCaja::where('estado', 'ABIERTO')->exists();

            if (! $cajaGeneralAbierta) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'No se pueden registrar ventas. La caja general del negocio está cerrada.',
                ], 400);
            }

            $datosVenta = $request->safe()->except(['detalles']);
            $datosVenta['cliente_id'] = $datosVenta['cliente_id'] ?? 1;

            $tipoFactura = $datosVenta['tipo_factura'] ?? null;
            if (($tipoFactura === '03' || $tipoFactura === 'CCF') && (int) $datosVenta['cliente_id'] === 1) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Para emitir Comprobante de Crédito Fiscal debe seleccionar un cliente registrado.',
                ], 400);
            }

            $aperturaVenta = AperturaVenta::where('cajero_id', auth()->id())
                ->where('estado', 'ABIERTA')
                ->first();

            DB::transaction(function () use ($request, &$aperturaVenta, $kardexService, $datosVenta) {

                $venta = Venta::create([
                    ...$datosVenta,
                    'numero_factura' => $this->numeroFactura(),
                    'apertura_venta_id' => $aperturaVenta?->id,
                    'vendido_por' => auth()->id(),
                ]);

                foreach ($request->validated()['detalles'] as $detalles) {

                    $presentacion = Presentacion::with('producto')->findOrFail($detalles['presentacion_id']);
                    $producto = $presentacion->producto;

                    $esGranel = ($producto->tipo_producto === 'GRANEL');
                    $factorConversion = (float) ($presentacion->factor_conversion ?? 1);
                    $precioUnitario = (float) $detalles['precio_unitario'];

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

                    // REDONDEAR Y ASIGNAR SUBTOTAL CON DESCUENTO
                    $descuentoTotalAcumulado = round($descuentoTotalAcumulado, 2);
                    $subtotalBruto = (float) $detalles['cantidad'] * $precioUnitario;
                    $subtotalFinal = $subtotalBruto - $descuentoTotalAcumulado;

                    // CREACIÓN DEL DETALLE DE VENTA CON SU DESCUENTO
                    $detalleVenta = DetalleVenta::create([
                        'venta_id' => $venta->id,
                        'nombre_producto' => $detalles['nombre_producto'],
                        'presentacion' => $detalles['presentacion'],
                        'cantidad' => $detalles['cantidad'],
                        'precio_unitario' => $detalles['precio_unitario'],
                        'subtotal' => $subtotalFinal,
                        'iva_aplicado' => $detalles['iva_aplicado'],
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

            });

            return response()->json([
                'status' => 'ok',
                'message' => 'Venta registrada con éxito',
                'apertura_pendiente' => is_null($aperturaVenta),
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
            response()->json([
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

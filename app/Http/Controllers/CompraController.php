<?php

namespace App\Http\Controllers;

use App\Http\Requests\Compra\StoreCompraRequest;
use App\Models\Compra;
use App\Models\Lote;
use App\Models\Presentacion;
use App\Models\Producto;
use App\Services\KardexService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CompraController extends Controller
{
    protected KardexService $kardexService;

    public function __construct(KardexService $kardexService)
    {
        $this->kardexService = $kardexService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        try {
            $resultado = Compra::query()
                ->with(['proveedor:id,nombre'])
                ->select([
                    'id',
                    'fecha_emision',
                    'tipo_dte',
                    'numero_documento',
                    'monto_total',
                    'estado_pago',
                    'fecha_vencimiento_pago',
                    'proveedor_id',
                ]);

            $fechaDesde = $request->fecha_desde ?? Carbon::now()->startOfMonth()->toDateString();
            $fechaHasta = $request->fecha_hasta ?? Carbon::now()->endOfMonth()->toDateString();

            $resultado->whereBetween('fecha_emision', [
                $fechaDesde,
                $fechaHasta,
            ]);

            if ($request->tipo_documento) {
                $resultado->where('tipo_dte', $request->tipo_documento);
            }
            if ($request->estado_pago) {
                $resultado->where('estado_pago', $request->estado_pago);
            }
            if ($request->proveedor) {
                $resultado->where('proveedor_id', $request->proveedor);
            }

            $compras = $resultado
                ->orderBy('created_at', 'desc')
                ->paginate(5);

            $itemsFormateados = collect($compras->items())->map(function ($compra) {
                return [
                    'id' => $compra->id,
                    'fechaEmision' => date('d-m-Y', strtotime($compra->fecha_emision)),
                    'proveedor' => $compra->proveedor->nombre ?? 'Sin Proveedor',
                    'tipoDocumento' => $compra->tipo_dte,
                    'numDocumento' => $compra->numero_documento,
                    'precioFactura' => number_format($compra->monto_total, 2, '.', ','),
                    'estadoPago' => strtoupper($compra->estado_pago),
                ];
            });

            return response()->json([
                'compras' => $itemsFormateados,
                'current_page' => $compras->currentPage(),
                'last_page' => $compras->lastPage(),
                'per_page' => $compras->perPage(),
                'total' => $compras->total(),
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error interno del servidor',
            ], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreCompraRequest $request)
    {
        try {
            $alertasGanancia = [];

            DB::transaction(function () use ($request, &$alertasGanancia) {

                $compra = Compra::create([
                    ...$request->safe()->except(['detalles']),
                    'usuario_id' => auth()->id(),
                ]);

                foreach ($request->validated()['detalles'] as $detalle) {

                    $lote = Lote::create([
                        ...$detalle['lote'],
                        'lote_interno' => $this->generarLoteInterno(),
                        'cantidad_actual' => $detalle['lote']['cantidad_inicial'],
                    ]);

                    $compra->detallesCompra()->create([
                        'cantidad_facturada' => $detalle['cantidad_facturada'],
                        'cantidad_bonificada' => $detalle['cantidad_bonificada'],
                        'precio_unitario_factura' => $detalle['precio_unitario_factura'],
                        'iva_linea' => $detalle['iva_linea'],
                        'descuento_linea' => $detalle['descuento_linea'],
                        'sub_total' => $detalle['sub_total'],
                        'lote_id' => $lote->id,
                    ]);

                    $presentacionId = $detalle['presentacion_id'] ?? $detalle['lote']['presentacion_id'];
                    $presentacionKardex = Presentacion::with('producto.categoria')->findOrFail($presentacionId);

                    $cantidadFisicaIngresada = (float) ($detalle['cantidad_facturada'] + ($detalle['cantidad_bonificada'] ?? 0));

                    $this->kardexService->registrarEntrada(
                        $presentacionKardex,
                        $lote,
                        $cantidadFisicaIngresada,
                        $compra,
                        $compra->numero_documento ?? $compra->id,
                        'Entrada por Compra '.($compra->numero_documento ?? ('#'.$compra->id))
                    );

                    
                    // EVALUACIÓN EN MEMORIA DEL COSTO PROMEDIO PONDERADO (CPP) Y GANANCIA
                    $producto = $presentacionKardex->producto;
                    $porcentajeMinimoRequerido = $producto->porcentaje_ganancia_efectivo;

                    // 1. Obtener la suma del stock y costo preexistente en lotes activos
                    $lotesActivos = Lote::where('estado', 'ACTIVO')
                        ->where('cantidad_actual', '>', 0)
                        ->where(function ($q) use ($producto, $presentacionKardex) {
                            if ($producto->tipo_producto === 'GRANEL') {
                                $q->where('producto_id', $producto->id);
                            } else {
                                $q->where('presentacion_id', $presentacionKardex->id);
                            }
                        })
                        ->get();

                    $stockTotal = $lotesActivos->sum('cantidad_actual');
                    
                    $valorTotalInvertido = $lotesActivos->sum(function ($l) {
                        $costoNeto = $l->costo_unitario_compra * (1 - ($l->porcentaje_descuento ?? 0) / 100);
                        return $l->cantidad_actual * $costoNeto;
                    });

                    $costoPromedioUnidadBase = $stockTotal > 0 ? ($valorTotalInvertido / $stockTotal) : 0;

                    // 2. Evaluar la ganancia real en las presentaciones activas del producto
                    $presentacionesAEvaluar = $producto->tipo_producto === 'GRANEL'
                        ? $producto->presentaciones()->where('activo', true)->get()
                        : collect([$presentacionKardex]);

                    foreach ($presentacionesAEvaluar as $pres) {
                        $costoPresentacion = $costoPromedioUnidadBase * $pres->factor_conversion;
                        $precioVenta = (float) $pres->precio_venta;

                        $gananciaDinero = $precioVenta - $costoPresentacion;
                        $porcentajeGananciaActual = $precioVenta > 0 ? ($gananciaDinero / $precioVenta) * 100 : 0;

                        if ($porcentajeGananciaActual < $porcentajeMinimoRequerido) {
                            $precioVentaSugerido = $costoPresentacion / (1 - ($porcentajeMinimoRequerido / 100));

                            $alertasGanancia[] = [
                                'presentacion_id' => $pres->id,
                                'producto_nombre' => $producto->nombre,
                                'presentacion_nombre' => $pres->nombre,
                                'costo_promedio_proyectado' => round($costoPresentacion, 2),
                                'precio_venta_actual' => round($precioVenta, 2),
                                'ganancia_dinero_actual' => round($gananciaDinero, 2),
                                'porcentaje_ganancia_actual' => round($porcentajeGananciaActual, 1),
                                'porcentaje_ganancia_requerido' => round($porcentajeMinimoRequerido, 1),
                                'precio_venta_sugerido' => round($precioVentaSugerido, 2),
                            ];
                        }
                    }
                }
            });

            // Si existen presentaciones que violan la regla de ganancia, se retorna status 'warning' para SweetAlert2
            if (! empty($alertasGanancia)) {
                return response()->json([
                    'status' => 'warning',
                    'message' => 'La compra fue registrada, pero la ganancia de algunas presentaciones de tus producto cayó por debajo del porcentaje mínimo.',
                    'requiere_ajuste_precios' => true,
                    'alertas' => $alertasGanancia,
                ], 200);
            }

            return response()->json([
                'status' => 'ok',
                'message' => 'Documento de compra y Detalles de los lotes han sido guardados',
            ], 201);

        } catch (ModelNotFoundException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'El producto o la presentación especificada en el detalle no existe en el sistema.',
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error interno del servidor',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        try {
            $compras = Compra::with(['proveedor:id,nombre', 'detallesCompra',
                'detallesCompra.lote', 'detallesCompra.lote.presentacion.producto:id,nombre', 'detallesCompra.lote.presentacion.producto:id,nombre'])
                ->findOrFail($id);

            return response()->json([
                'status' => 'ok',
                'data' => $compras,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error interno en el Servidor',
            ], 500);
        }
    }

    public function generarLoteInterno()
    {
        $fecha = now()->format('Ymd');

        $resultado = DB::select(
            'SELECT lote_interno FROM lotes
         WHERE lote_interno LIKE :buscar
         ORDER BY lote_interno DESC
         LIMIT 1',
            ['buscar' => "LOT-{$fecha}-%"]
        );

        $ultimo = ! empty($resultado) ? $resultado[0]->lote_interno : null;

        if ($ultimo) {
            $secuencia = (int) substr($ultimo, -4) + 1;
        } else {
            $secuencia = 1;
        }

        return 'LOT-'.$fecha.'-'.str_pad($secuencia, 4, '0', STR_PAD_LEFT);
    }

    public function anularCompra(string $id)
    {
        try {

            DB::beginTransaction();

            $compra = Compra::with('detallesCompra')->findOrFail($id);

            if ($compra->es_anulado) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Esta compra ya fue anulada previamente',
                ], 422);
            }

            $lotesBloqueados = [];

            foreach ($compra->detallesCompra as $detalle) {
                $lote = Lote::where('id', $detalle->lote_id)->lockForUpdate()->first();

                if ($lote->cantidad_inicial != $lote->cantidad_actual) {
                    DB::rollBack();

                    return response()->json([
                        'status' => 'error',
                        'message' => 'La compra no se puede anular,por que unos de sus productos ya fue vendido',
                    ], 422);
                }

                $lotesBloqueados[] = [
                    'detalle' => $detalle,
                    'lote' => $lote,
                ];
            }

            foreach ($lotesBloqueados as $item) {
                $item['detalle']->es_anulado = true;
                $item['detalle']->save();

                $item['lote']->estado = 'ANULADO';
                $item['lote']->save();

                $presentacion = Presentacion::with('producto')->findOrFail($item['lote']->presentacion_id);
                $cantidadFisicaOriginal = (float) ($item['detalle']->cantidad_facturada + ($item['detalle']->cantidad_bonificada ?? 0));

                $this->kardexService->registrarAnulacionCompra(
                    $presentacion,
                    $item['lote'],
                    $cantidadFisicaOriginal,
                    $compra,
                    $compra->numero_documento ?? $compra->id,
                    'Anulación de Compra '.($compra->numero_documento ?? ('#'.$compra->id))
                );

            }

            $compra->es_anulado = true;
            $compra->save();

            DB::commit();

            return response()->json([
                'status' => 'ok',
                'message' => 'La compra se anuló con éxito',
            ], 200);

        } catch (ModelNotFoundException $mdn) {
            DB::rollback();

            return response()->json([
                'status' => 'error',
                'message' => 'La compra no existe',
            ], 404);

        } catch (\Throwable $th) {
            DB::rollback();

            return response()->json([
                'status' => 'error',
                'message' => 'Error interno en el servidor, la compra no se pudo anular',
            ], 500);
        }
    }
}
<?php

namespace App\Http\Controllers;

use App\Http\Requests\Venta\StoreVentaRequest;
use App\Models\AperturaVenta;
use App\Models\DetalleVenta;
use App\Models\Lote;
use App\Models\LoteDetalleVenta;
use App\Models\Venta;
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
                ->paginate($request->per_page ?? 12);

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
    public function store(StoreVentaRequest $request)
    {
        try {

            $aperturaVenta = AperturaVenta::where('cajero_id', auth()->id())
                ->where('estado', 'ABIERTA')
                ->first();

            DB::transaction(function () use ($request, &$aperturaVenta) {

                $venta = Venta::create([
                    ...$request->safe()->except(['detalles']),
                    'numero_factura' => $this->numeroFactura(),
                    'apertura_venta_id' => $aperturaVenta?->id,
                    'vendido_por' => auth()->id(),
                ]);

                foreach ($request->validated()['detalles'] as $detalles) {

                    $detalleVenta = DetalleVenta::create([
                        'venta_id' => $venta->id,
                        'nombre_producto' => $detalles['nombre_producto'],
                        'presentacion' => $detalles['presentacion'],
                        'cantidad' => $detalles['cantidad'],
                        'precio_unitario' => $detalles['precio_unitario'],
                        'subtotal' => $detalles['subtotal'],
                        'iva_aplicado' => $detalles['iva_aplicado'],
                        'unidad_base' => $detalles['unidad_base'],
                        'descuento_aplicado' => $detalles['descuento_aplicado'],

                    ]);

                    $cantidadSolicitada = $detalles['cantidad'];

                    while ($cantidadSolicitada > 0) {

                        $lote = Lote::where('presentacion_id', $detalles['presentacion_id'])
                            ->where('cantidad_actual', '>', 0)
                            ->where('estado', 'ACTIVO')
                            ->orderByRaw('fecha_vencimiento ASC NULLS LAST')
                            ->orderBy('created_at', 'ASC')
                            ->lockForUpdate()
                            ->firstOrFail();

                        if ($lote->cantidad_actual >= $cantidadSolicitada) {

                            LoteDetalleVenta::create([
                                'detalle_venta_id' => $detalleVenta->id,
                                'lote_id' => $lote->id,
                                'cantidad_tomada' => $cantidadSolicitada,
                            ]);

                            $lote->cantidad_actual = bcsub($lote->cantidad_actual, $cantidadSolicitada, 3);

                            if ($lote->cantidad_actual == 0) {
                                $lote->estado = 'AGOTADO';
                            }
                            $lote->save();
                            $cantidadSolicitada = 0;

                        } else {

                            $stockEntregado = $lote->cantidad_actual;

                            LoteDetalleVenta::create([
                                'detalle_venta_id' => $detalleVenta->id,
                                'lote_id' => $lote->id,
                                'cantidad_tomada' => $stockEntregado,
                            ]);

                            $lote->cantidad_actual = 0;
                            $lote->estado = 'AGOTADO';
                            $lote->update();

                            $cantidadSolicitada = bcsub($cantidadSolicitada, $stockEntregado, 3);

                        }

                    }

                }

            });

            return response()->json([
                'status' => 'ok',
                'message' => 'Venta registrada con éxito',
                'apertura_pendiente' => is_null($aperturaVenta),
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'No hay stock suficiente y no se puedo registrar la venta',

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

<?php

namespace App\Http\Controllers;

use App\Http\Requests\Venta\StoreVentaRequest;
use App\Models\DetalleVenta;
use App\Models\Lote;
use App\Models\LoteDetalleVenta;
use App\Models\Venta;
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
                ->with(['cliente:id,nombre,tipo_persona'])
                ->select([
                    'id',
                    'numero_factura',
                    'total',
                    'tipo_pago',
                    'estado',
                    'cliente_id',
                    'apertura_caja_id',
                    'created_at',
                ]);

            if ($request->cliente) {
                $resultados->where('cliente_id', $request->cliente);
            }
            if ($request->cajaApertura) {
                $resultados->where('apertura_caja_id', $request->cajaApertura);
            }

            $ventas = $resultados
                ->orderBy('created_at', 'desc')
                ->paginate($request->per_page ?? 12);

            return response()->json($ventas);

        } catch (\Throwable $th) {
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

            DB::transaction(function () use ($request) {

                $venta = Venta::create([
                    ...$request->safe()->except(['detalles']),
                    'numero_factura' => $this->numeroFactura(),
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

                            $lote->cantidad_actual = $lote->cantidad_actual - $cantidadSolicitada;

                            if ($lote->cantidad_actual == 0) {
                                $lote->estado = 'AGOTADO';
                            }
                            $lote->update();
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

                            $cantidadSolicitada = $cantidadSolicitada - $stockEntregado;

                        }

                    }

                }

            });

            return response()->json([
                'status' => 'ok',
                'message' => 'Venta registrada con éxito',
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Ocurrío un error y no se pudo registrar la compra',
                'errorMessage' => $e->getMessage(),
            ], 500);

        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
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

<?php

namespace App\Http\Controllers;

use App\Http\Requests\Venta\StoreVentaRequest;
use App\Models\DetalleVenta;
use App\Models\Lote;
use App\Models\Venta;
use Illuminate\Http\Request;

class VentaController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
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
                            ->orderBy('fecha_vencimiento', 'asc')
                            ->firstOrFail();

                        if ($lote->cantidad_actual >= $cantidadSolicitada) {

                            LoteDetalleVenta::create([
                                'detalle_venta_id' => $detalleVenta->id,
                                'lote_id' => $lote->id,
                                'cantidad_tomada' => $cantidadSolicitada,
                            ]);

                            $lote->cantidad_actual = $lote->cantidad_actual - $cantidadSolicitada;

                            if($lote->cantidad_actual == 0){
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
}

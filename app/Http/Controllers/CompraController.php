<?php

namespace App\Http\Controllers;

use App\Http\Requests\Compra\StoreCompraRequest;
use App\Models\Compra;
use App\Models\Lote;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CompraController extends Controller
{
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
            

                $fechaDesde = $request->fecha_desde??Carbon::now()->startOfMonth()->toDateString();
                $fechaHasta= $request->fecha_hasta??Carbon::now()->endOfMonth()->toDateString();

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

            return response()->json($compras);
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

            DB::transaction(function () use ($request) {

                $compra = Compra::create([
                    ...$request->safe()->except(['detalles']),
                    'usuario_id' => auth()->id(),
                ]);

                foreach ($request->validated()['detalles'] as $detalle) {

                    $lote = Lote::create([
                        ...$detalle['lote'],
                        'lote_interno' => $this->generarLoteInterno(),
                        'cantidad_actual' => $detalle['cantidad_facturada'],
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
                }
            });

            return response()->json([
                'status' => 'ok',
                'message' => 'Documento de compra y Detalles de los lotes han sido guardados',
            ], 201);
        } catch (\Exception $e) {

            return response()->json([
                'status' => 'error',
                'message' => "Error interno del servidor",
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        try {
            $compras = Compra::with(['proveedor:id,nombre','detallesCompra', 
            'detallesCompra.lote', 'detallesCompra.lote.presentacion.producto:id,nombre', 'detallesCompra.lote.presentacion.producto:id,nombre' ])
            ->findOrFail($id);

             
            return response()->json([
                'status' => 'ok',
                'data' => $compras 
            ],200);

        } catch (\Exception $e) {
             return response()->json([
                'status' => 'error',
                'message' => 'Error interno en el Servidor' . $e->getMessage()
            ],500);
        }
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

        $ultimo = !empty($resultado) ? $resultado[0]->lote_interno : null;

        if ($ultimo) {
            $secuencia = (int) substr($ultimo, -4) + 1;
        } else {
            $secuencia = 1;
        }

        return 'LOT-' . $fecha . '-' . str_pad($secuencia, 4, '0', STR_PAD_LEFT);
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Lote;
use App\Models\Presentacion;

use Illuminate\Http\Request;

class LoteController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        try {
            if (! auth()->user()->hasAnyRole(['ADMIN','CAJERO'])) {
                return response()->json([
                    'message' => 'No autorizado',
                ], 403);
            }

            $presentacion = Presentacion::with('producto')->find($request->presentacion_id);

            if (! $presentacion) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Presentación inexistente'
                ], 404);
            }

            $perPage = $request->get('per_page', 5);
            $page = $request->get('page', 1);

      
            $consultarLotes = Lote::query();
          

           if($presentacion->producto?->tipo_producto === 'GRANEL'){
                $consultarLotes->where('producto_id' , $presentacion->producto_id);
           }else{
              $consultarLotes->where('presentacion_id', $presentacion->id);
           }

            $lotes = $consultarLotes
                ->select(
                    'id',
                    'lote_interno',
                    'lote_fabricante',
                    'fecha_vencimiento',
                    'cantidad_inicial',
                    'cantidad_actual',
                    'costo_unitario_compra',
                    'estado'
                )
                ->orderByRaw('fecha_vencimiento ASC NULLS LAST')
                ->paginate($perPage, ['*'], 'page', $page);

            return response()->json([
                'status' => 'ok',
                'data' => $lotes->items(),
                'total' => $lotes->total(),
                'per_page' => $lotes->perPage(),
                'current_page' => $lotes->currentPage(),
                'last_page' => $lotes->lastPage(),
            ]);

        } catch (\Exception $e) {

            return response()->json([
                'status' => 'error',
                'message' => 'Error servidor',
            ], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
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

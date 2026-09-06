<?php

namespace App\Http\Controllers;

use App\Http\Requests\Kardex\GetKardexRequest;
use App\Models\Kardex;
use Illuminate\Http\JsonResponse;

class KardexController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(GetKardexRequest $request): JsonResponse
    {
    
        $modoCosteo = $request->input('modo_costeo', 'PEPS');

        $fechaInicio = $request->filled('fecha_inicio') 
            ? $request->fecha_inicio 
            : now()->startOfMonth()->toDateString();

        $fechaFin = $request->filled('fecha_fin') 
            ? $request->fecha_fin 
            : now()->endOfMonth()->toDateString();

        $query = Kardex::with(['producto', 'presentacion', 'lote', 'usuario', 'origen'])
            ->where('producto_id', $request->producto_id)
            ->whereBetween('created_at', [$fechaInicio . ' 00:00:00', $fechaFin . ' 23:59:59'])
            ->when($request->filled('presentacion_id'), function ($q) use ($request) {
                return $q->where('presentacion_id', $request->presentacion_id);
            })
            ->orderBy('id', 'asc');

        $movimientos = $query->paginate($request->input('per_page', 15));

        $movimientos->getCollection()->transform(function ($registro) use ($modoCosteo) {
            if ($modoCosteo === 'CPP') {
                $registro->costo_aplicado = ((float) $registro->costo_promedio_ponderado > 0)
                    ? $registro->costo_promedio_ponderado
                    : $registro->costo_unitario;
            } else {
                $registro->costo_aplicado = $registro->costo_unitario;
            }

            return $registro;
        });

        return response()->json([
            'status'       => 'ok',
            'modo_costeo'  => $modoCosteo,
            'fecha_inicio' => $fechaInicio,
            'fecha_fin'    => $fechaFin,
            'data'         => $movimientos,
        ], 200);
    }
}
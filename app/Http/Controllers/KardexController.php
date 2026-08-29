<?php

namespace App\Http\Controllers;

use App\Models\Kardex;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class KardexController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request): JsonResponse
    {
        $request->validate([
            'producto_id'     => 'required|exists:productos,id',
            'presentacion_id' => 'nullable|exists:presentaciones,id',
            'modo_costeo'     => 'nullable|in:PEPS,CPP',
            'fecha_inicio'    => 'nullable|date',
            'fecha_fin'       => 'nullable|date|after_or_equal:fecha_inicio',
            'per_page'        => 'nullable|integer|min:1|max:100',
        ]);

        $modoCosteo = $request->input('modo_costeo', 'PEPS');

        $query = Kardex::with(['producto', 'presentacion', 'lote', 'usuario', 'origen'])
            ->where('producto_id', $request->producto_id)
            ->when($request->filled('presentacion_id'), function ($q) use ($request) {
                return $q->where('presentacion_id', $request->presentacion_id);
            })
            ->when($request->filled('fecha_inicio'), function ($q) use ($request) {
                return $q->whereDate('created_at', '>=', $request->fecha_inicio);
            })
            ->when($request->filled('fecha_fin'), function ($q) use ($request) {
                return $q->whereDate('created_at', '<=', $request->fecha_fin);
            })
            ->orderBy('id', 'asc');

        $movimientos = $query->paginate($request->input('per_page', 15));

        // Transformación visual según el modo de costeo seleccionado
        $movimientos->getCollection()->transform(function ($registro) use ($modoCosteo) {
            if ($modoCosteo === 'CPP') {
                // 🔹 Si el CPP calculado en la BD es mayor a 0, lo usamos.
                // Si es 0 (por saldo cero o negativo), usamos el costo unitario del lote como respaldo de valor.
                $registro->costo_aplicado = ((float) $registro->costo_promedio_ponderado > 0)
                    ? $registro->costo_promedio_ponderado
                    : $registro->costo_unitario;
            } else {
                // Modo PEPS: Muestra siempre el costo unitario real del lote
                $registro->costo_aplicado = $registro->costo_unitario;
            }

            return $registro;
        });

        return response()->json([
            'status' => 'ok',
            'modo_costeo' => $modoCosteo,
            'data' => $movimientos,
        ], 200);
    }
}
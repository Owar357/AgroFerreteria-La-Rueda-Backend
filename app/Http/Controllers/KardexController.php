<?php

namespace App\Http\Controllers;

use App\Http\Requests\Kardex\GetKardexRequest;
use App\Models\Kardex;
use Illuminate\Http\JsonResponse;

class KardexController extends Controller
{
    public function __invoke(GetKardexRequest $request, $producto)
    {
        $modoCosteo = $request->input('modo_costeo', 'PEPS');

        $fechaInicio = $request->filled('fecha_inicio')
            ? $request->fecha_inicio
            : now()->startOfMonth()->toDateString();

        $fechaFin = $request->filled('fecha_fin')
            ? $request->fecha_fin
            : now()->endOfMonth()->toDateString();

        // 1. Base Query usando el $producto de la URL
        $query = Kardex::with(['producto', 'presentacion', 'lote', 'usuario', 'origen'])
            ->where('producto_id', $producto)
            ->whereBetween('created_at', [$fechaInicio.' 00:00:00', $fechaFin.' 23:59:59'])
            ->when($request->filled('presentacion_id'), function ($q) use ($request) {
                return $q->where('presentacion_id', $request->presentacion_id);
            })
            ->when($request->filled('tipo_movimiento'), function ($q) use ($request) {
                return $q->where('tipo_movimiento', $request->tipo_movimiento);
            });

        // 2. Cálculo eficiente de métricas
        $totales = (clone $query)->selectRaw('
        SUM(cantidad_entrada) as total_entradas,
        SUM(cantidad_salida) as total_salidas,
        SUM(monto_entrante) as monto_total_entradas,
        SUM(monto_saliente) as monto_total_salidas
    ')->first();

        $metricas = [
            'total_entradas' => (float) ($totales->total_entradas ?? 0),
            'total_salidas' => (float) ($totales->total_salidas ?? 0),
            'monto_total_entradas' => (float) ($totales->monto_total_entradas ?? 0),
            'monto_total_salidas' => (float) ($totales->monto_total_salidas ?? 0),
        ];

        // 3. Paginación
        $movimientos = $query->orderBy('id', 'asc')
            ->paginate($request->input('per_page', 15));

        // 4. Transformación de costo según el modo de costeo
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

        return new JsonResponse([
            'status' => 'ok',
            'modo_costeo' => $modoCosteo,
            'fecha_inicio' => $fechaInicio,
            'fecha_fin' => $fechaFin,
            'metricas' => $metricas,
            'data' => $movimientos,
        ], 200);
    }
}

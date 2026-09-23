<?php

namespace App\Http\Controllers\Reportes\Compras;

use App\Http\Controllers\Controller;
use App\Models\Compra;
use Illuminate\Http\Request;

class ComprasPorProveedorDatosController extends Controller
{
    /**
     * Devuelve en JSON las compras filtradas por rango de fecha,
     * para alimentar la tabla del frontend.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function __invoke(Request $request)
    {
        $request->validate([
            'fecha_inicio' => 'required|date',
            'fecha_fin'    => 'required|date|after_or_equal:fecha_inicio',
        ]);

        $fechaInicio = $request->input('fecha_inicio');
        $fechaFin    = $request->input('fecha_fin');

        $compras = Compra::with('proveedor')
            ->whereBetween('fecha_emision', [$fechaInicio, $fechaFin])
            ->where('es_anulado', false)
            ->orderBy('fecha_emision', 'desc')
            ->get();

        return response()->json([
            'data' => $compras,
        ]);
    }
}
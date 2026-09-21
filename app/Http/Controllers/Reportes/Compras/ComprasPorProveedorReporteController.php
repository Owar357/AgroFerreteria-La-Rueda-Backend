<?php

namespace App\Http\Controllers\Reportes\Compras;

use App\Http\Controllers\Controller;
use App\Models\Proveedor;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class ComprasPorProveedorReporteController extends Controller
{
    /**
     * Genera el PDF del reporte detallado de compras por proveedor con sus productos.
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function __invoke(Request $request)
    {
        $request->validate([
            'fecha_inicio' => 'required|date',
            'fecha_fin'    => 'required|date|after_or_equal:fecha_inicio',
        ]);

        $fechaInicio = $request->input('fecha_inicio');
        $fechaFin    = $request->input('fecha_fin');

        $proveedores = Proveedor::whereHas('compras', function ($query) use ($fechaInicio, $fechaFin) {
            $query->whereBetween('fecha_emision', [$fechaInicio, $fechaFin])
                  ->where('es_anulado', false);
        })
        ->with(['compras' => function ($query) use ($fechaInicio, $fechaFin) {
            $query->whereBetween('fecha_emision', [$fechaInicio, $fechaFin])
                  ->where('es_anulado', false)
                  ->with(['detallesCompra' => function ($detQuery) {
                      $detQuery->where('es_anulado', false)
                               ->with(['lote.producto', 'lote.presentacion']);
                  }]);
        }])
        ->get();

        $pdf = Pdf::loadView('reportes.compras.compras-por-proveedor', compact('proveedores', 'fechaInicio', 'fechaFin'));

        return $pdf->stream("compras-detalladas-por-proveedor-{$fechaInicio}-a-{$fechaFin}.pdf");
    }
}
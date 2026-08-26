<?php

namespace App\Http\Controllers\Reportes;

use App\Http\Controllers\Controller;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReporteComprasController extends Controller
{
    public function comprasPorProveedor(Request $request)
    {
        $request->validate([
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'required|date|after_or_equal:fecha_inicio',
        ]);

        $fecha_inicio = $request->fecha_inicio;
        $fecha_fin = $request->fecha_fin;

        $compras = DB::table('compras')
            ->join('proveedores', 'compras.proveedor_id', '=', 'proveedores.id')
            ->whereBetween('compras.fecha_emision', [
                $fecha_inicio,
                $fecha_fin
            ])
            ->where('compras.es_anulado', false)
            ->select(
                'proveedores.id',
                'proveedores.nombre',
                DB::raw('SUM(compras.monto_total) as monto_total'),
                DB::raw('COUNT(compras.id) as numero_compras')
            )
            ->groupBy(
                'proveedores.id',
                'proveedores.nombre'
            )
            ->orderBy('monto_total', 'desc')
            ->get();

        foreach ($compras as $compra) {

            $compra->productos_distintos = DB::table('detalles_compra')
                ->join('compras', 'detalles_compra.compra_id', '=', 'compras.id')
                ->where('compras.proveedor_id', $compra->id)
                ->whereBetween('compras.fecha_emision', [
                    $fecha_inicio,
                    $fecha_fin
                ])
                ->where('compras.es_anulado', false)
                ->where('detalles_compra.es_anulado', false)
                ->distinct()
                ->count('detalles_compra.lote_id');
        }

        $pdf = Pdf::loadView('reportes.compras-por-proveedor', [
            'compras' => $compras,
            'fecha_inicio' => $fecha_inicio,
            'fecha_fin' => $fecha_fin
        ]);

        return $pdf->stream('compras-por-proveedor.pdf');
    }
}

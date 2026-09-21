<?php

namespace App\Http\Controllers\Reportes\Inventario;

use App\Http\Controllers\Controller;
use App\Models\Lote;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductosPorVencerReporteController extends Controller
{
    /**
     * Genera el PDF del reporte de productos próximos a vencer con alerta tipo semáforo.
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function __invoke(Request $request)
    {
        $request->validate([
            'dias_umbral' => 'nullable|integer|min:1',
        ]);

        $diasUmbral = (int) ($request->input('dias_umbral', 30));

        $hoy = now()->startOfDay();
        $fechaLimite = now()->addDays($diasUmbral)->endOfDay();

        $lotes = Lote::leftJoin('presentaciones', 'lotes.presentacion_id', '=', 'presentaciones.id')
            ->join('productos', 'lotes.producto_id', '=', 'productos.id')
            ->join('unidad_medidas', 'productos.unidad_medida_id', '=', 'unidad_medidas.id')
            ->where('lotes.cantidad_actual', '>', 0)
            ->where('lotes.estado', '=', 'ACTIVO')
            ->whereNotNull('lotes.fecha_vencimiento')
            ->whereBetween('lotes.fecha_vencimiento', [
                $hoy->toDateString(),
                $fechaLimite->toDateString()
            ])
            ->select(
                'lotes.id',
                'lotes.lote_interno',
                'lotes.fecha_vencimiento',
                'lotes.cantidad_actual',
                'productos.codigo as sku',
                'productos.nombre as producto_nombre',
                'productos.tipo_producto',
                'presentaciones.nombre as presentacion_nombre',
                'unidad_medidas.abreviatura as unidad_medida',
                DB::raw("(lotes.fecha_vencimiento - CURRENT_DATE) as dias_restantes")
            )
            ->orderBy('lotes.fecha_vencimiento', 'asc')
            ->get();

        $fechaEmision = now();

        $pdf = Pdf::loadView('reportes.inventario.productos-por-vencer', [
            'lotes'        => $lotes,
            'diasUmbral'   => $diasUmbral,
            'fechaEmision' => $fechaEmision,
        ]);

        return $pdf->stream("productos-por-vencer-{$diasUmbral}-dias.pdf");
    }
}
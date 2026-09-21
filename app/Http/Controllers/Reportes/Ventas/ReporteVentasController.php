<?php

namespace App\Http\Controllers\Reportes\Ventas;

use App\Http\Controllers\Controller;
use App\Models\Venta;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Carbon\Carbon;

class ReporteVentasController extends Controller
{
    public function __invoke(Request $request)
    {
        $ventas = Venta::select(
            'numero_factura',
            'created_at',
            'tipo_pago',
            'gravado',
            'iva',
            'total'
        );

        $fecha_desde = $request->filled('fecha_desde') ? Carbon::parse($request->fecha_desde)->startOfDay() : null;
        $fecha_hasta = $request->filled('fecha_hasta') ? Carbon::parse($request->fecha_hasta)->endOfDay() : null;

        if ($fecha_desde && $fecha_hasta) {
            $ventas->whereBetween('created_at', [$fecha_desde, $fecha_hasta]);
        }

        $ventas = $ventas->orderBy('created_at', 'asc')->get();

        $pdf = Pdf::loadView('reportes.ventas.ventas', [
            'ventas' => $ventas,
            'fecha_desde' => $request->fecha_desde,
            'fecha_hasta' => $request->fecha_hasta,
        ]);

        $nombreArchivo = "reporte-ventas";
        if ($request->filled('fecha_desde') && $request->filled('fecha_hasta')) {
            $nombreArchivo .= "-{$request->fecha_desde}_al_{$request->fecha_hasta}";
        }

        return $pdf->stream("{$nombreArchivo}.pdf");
    }
}
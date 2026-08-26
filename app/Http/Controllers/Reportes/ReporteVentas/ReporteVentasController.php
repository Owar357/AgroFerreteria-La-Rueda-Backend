<?php

namespace App\Http\Controllers\Reportes\ReporteVentas;

use App\Http\Controllers\Controller;
use App\Models\Venta;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class ReporteVentasController extends Controller
{
    public function ventas(Request $request)
    {
        $ventas = Venta::select(
            'numero_factura',
            'created_at',
            'tipo_pago',
            'gravado',
            'iva',
            'total'
        );

        $fecha_desde = null;
        $fecha_hasta = null;

        if ($request->filled('fecha_desde') && $request->filled('fecha_hasta')) {

            $ventas->whereBetween('created_at', [$request->fecha_desde, $request->fecha_hasta]);
        }

        // Traemos los datos de la Db
        $ventas = $ventas->orderBy('created_at', 'asc')->get();

        $pdf = Pdf::loadView('reportes.ventas', [

            'ventas' => $ventas,
            'fecha_desde' => $request->fecha_desde,
            'fecha_hasta' => $request->fecha_hasta,

        ]);

        return $pdf->stream('reporte.pdf');
    }

    public function ticket($id)
    {
        $venta = Venta::with([
            'cliente',
            'vendidoPor',
            'detallesVenta',
        ])->findOrFail($id);

        $pdf = Pdf::loadView('reportes.ticket', compact('venta'))
            ->setPaper([0, 0, 240.77, 900], 'portrait');

        return $pdf->stream('Ticket-'.$venta->numero_factura.'.pdf');
    }
}

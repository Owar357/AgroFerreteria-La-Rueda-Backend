<?php

namespace App\Http\Controllers;

use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\Venta;
use Illuminate\Http\Request;
use Carbon\Carbon;


class ReporteController extends Controller
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

        if ($request->filled('fecha_desde') && $request->filled('fecha_hasta')){

            $ventas->whereBetween('created_at', [$request->fecha_desde, $request->fecha_hasta]);
        }

        // Traemos los datos de la Db
        $ventas = $ventas->orderBy('created_at', 'asc')->get();

            $pdf = Pdf::loadView('reportes.ventas', [

                'ventas' => $ventas,
                'fecha_desde' => $request->fecha_desde,
                'fecha_hasta' => $request->fecha_hasta

            ]);

        return $pdf->stream('reporte.pdf');

    }

}

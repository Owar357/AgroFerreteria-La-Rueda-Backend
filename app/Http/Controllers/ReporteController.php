<?php

namespace App\Http\Controllers;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\Venta;

use Illuminate\Http\Request;

class ReporteController extends Controller
{
    public function ventas()
    {
        $ventas = Venta::select(
            'numero_factura',
            'created_at',
            'tipo_pago',
            'subtotal',
            'iva',
            'total'
        )->get();

        $pdf = Pdf::loadView('reportes.ventas', compact('ventas'));

        return $pdf->stream('reporte.pdf');

    }

}

<?php

namespace App\Http\Controllers\Reportes\Ventas;

use App\Http\Controllers\Controller;
use App\Models\Venta;
use Barryvdh\DomPDF\Facade\Pdf;

class TicketVentaController extends Controller
{
   public function __invoke($id)
    {
        $venta = Venta::with([
            'cliente',
            'vendidoPor',
            'detallesVenta'
        ])->findOrFail($id);


        $venta->gravado = $venta->gravado ?? $venta->total; 
        $venta->exento = $venta->exento ?? 0.00;
        $venta->iva = $venta->iva ?? 0.00;
        $venta->efectivo_recibido = $venta->efectivo_recibido ?? $venta->total;
        $venta->cambio = $venta->cambio ?? 0.00;

        $pdf = Pdf::loadView('reportes.ticket', compact('venta'))
            ->setPaper([0, 0, 226.77, 900], 'portrait');

        return $pdf->stream('Ticket-' . $venta->numero_factura . '.pdf');
    }
}

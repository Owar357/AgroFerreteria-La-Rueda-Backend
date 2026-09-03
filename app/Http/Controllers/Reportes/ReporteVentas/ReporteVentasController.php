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

        $anchoTicket = 226.77;

        $alturaBase = 380;

        $alturaPorProducto = 45;
        $cantidadProductos = $venta->detallesVenta->count();

        $tieneDescuento = $venta->detallesVenta->sum('descuento_aplicado') > 0;
        $esEfectivo = $venta->tipo_pago === 'EFECTIVO';
        $extraDescuento = $tieneDescuento ? 16 : 0;   // fila "Descuento Total"
        $extraEfectivo = $esEfectivo ? 32 : 0;        // filas "Efectivo Recibido" + "Cambio"

        $alturaCalculada = $alturaBase
            + ($cantidadProductos * $alturaPorProducto)
            + $extraDescuento
            + $extraEfectivo;

        $alturaMinima = 420;
        $alturaFinal = max($alturaCalculada, $alturaMinima);

        $pdf = Pdf::loadView('reportes.ticket', compact('venta'))
            ->setPaper([0, 0, $anchoTicket, $alturaFinal], 'portrait');

        return $pdf->stream('Ticket-'.$venta->numero_factura.'.pdf');

    }
}

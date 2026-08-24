<?php

namespace App\Http\Controllers;

use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\Venta;
use App\Models\User;
use App\Models\Compra;
use App\Models\Cliente;
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

        if ($request->filled('fecha_desde') && $request->filled('fecha_hasta')) {

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

    public function ticket($id)
    {
        $venta = Venta::with([
            'cliente',
            'vendidoPor',
            'detallesVenta'
        ])->findOrFail($id);



        $pdf = Pdf::loadView('reportes.ticket', compact('venta'))
            ->setPaper([0, 0, 240.77, 900], 'portrait');

        return $pdf->stream('Ticket-' . $venta->numero_factura . '.pdf');
    }

    public function flujoComprasVentas(Request $request)
    {
        $fecha_desde = Carbon::parse($request->fecha_desde);
        $fecha_hasta = Carbon::parse($request->fecha_hasta);

        $duracion = $fecha_desde->diffInDays($fecha_hasta) + 1;

        $fecha_anterior_hasta = $fecha_desde->copy()->subDay();
        $fecha_anterior_desde = $fecha_anterior_hasta->copy()->subDays($duracion - 1);

        $compras_actual = Compra::where('es_anulado', false)
            ->whereBetween('fecha_emision', [$fecha_desde, $fecha_hasta])
            ->sum('monto_total');

        $compras_anterior = Compra::where('es_anulado', false)
            ->whereBetween('fecha_emision', [$fecha_anterior_desde, $fecha_anterior_hasta])
            ->sum('monto_total');

        $ventas_actual = Venta::where('estado', 'PROCESADA')
            ->whereBetween('created_at', [$fecha_desde, $fecha_hasta])
            ->sum('total');

        $ventas_anterior = Venta::where('estado', 'PROCESADA')
            ->whereBetween('created_at', [$fecha_anterior_desde, $fecha_anterior_hasta])
            ->sum('total');

        $flujo_actual = $ventas_actual - $compras_actual;

        $flujo_anterior = $ventas_anterior - $compras_anterior;

        $variacion_compras = $compras_anterior != 0
            ? (($compras_actual - $compras_anterior) / $compras_anterior) * 100
            : 0;

        $variacion_ventas = $ventas_anterior != 0
            ? (($ventas_actual - $ventas_anterior) / $ventas_anterior) * 100
            : 0;

        $variacion_flujo = $flujo_anterior != 0
            ? (($flujo_actual - $flujo_anterior) / $flujo_anterior) * 100
            : 0;

        $pdf = Pdf::loadView('reportes.flujo-compras-ventas', [

            'fecha_desde' => $fecha_desde,
            'fecha_hasta' => $fecha_hasta,

            'fecha_anterior_desde' => $fecha_anterior_desde,
            'fecha_anterior_hasta' => $fecha_anterior_hasta,

            'compras_actual' => $compras_actual,
            'compras_anterior' => $compras_anterior,

            'ventas_actual' => $ventas_actual,
            'ventas_anterior' => $ventas_anterior,

            'flujo_actual' => $flujo_actual,
            'flujo_anterior' => $flujo_anterior,

            'variacion_compras' => $variacion_compras,
            'variacion_ventas' => $variacion_ventas,
            'variacion_flujo' => $variacion_flujo

        ]);

        return $pdf->stream('reporte-flujo-compras-ventas.pdf');
    }
}

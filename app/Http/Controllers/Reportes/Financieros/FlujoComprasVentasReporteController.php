<?php

namespace App\Http\Controllers\Reportes\Financieros;

use App\Http\Controllers\Controller;
use App\Models\Compra;
use App\Models\Venta;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;

class FlujoComprasVentasReporteController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request)
    {
        $request->validate([
            'fecha_desde' => 'required|date',
            'fecha_hasta' => 'required|date|after_or_equal:fecha_desde',
        ]);

        $fecha_desde = Carbon::parse($request->fecha_desde)->startOfDay();
        $fecha_hasta = Carbon::parse($request->fecha_hasta)->endOfDay();

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

        $pdf = Pdf::loadView('reportes.financieros.flujo-compras-ventas', [
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
            'variacion_flujo' => $variacion_flujo,
        ]);

        return $pdf->stream("reporte-flujo-compras-ventas-del-{$fecha_desde->format('Y-m-d')}_al-{$fecha_hasta->format('Y-m-d')}.pdf");
    }
}
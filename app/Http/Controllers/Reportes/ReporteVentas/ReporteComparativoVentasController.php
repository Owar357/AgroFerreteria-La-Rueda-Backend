<?php

namespace App\Http\Controllers\Reportes\ReporteVentas;

use App\Http\Controllers\Controller;
use App\Models\Venta;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ReporteComparativoVentasController extends Controller
{
    public function resumenVentas(Request $request)
    {
        $request->validate([
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'required|date|after_or_equal:fecha_inicio',
        ]);

        $fecha_inicio = Carbon::parse($request->fecha_inicio)->startOfDay();
        $fecha_fin = Carbon::parse($request->fecha_fin)->endOfDay();

        $ventas = Venta::where('estado', 'PROCESADA')
            ->whereBetween('created_at', [$fecha_inicio, $fecha_fin])
            ->get();

        $total_vendido = $ventas->sum('total');

        $numero_ventas = $ventas->count();

        $ticket_promedio = $numero_ventas != 0
            ? $total_vendido / $numero_ventas
            : 0;

        $duracion = $fecha_inicio->diffInDays($fecha_fin) + 1;

        $serie = [];

        if ($duracion <= 31) {

            $serie = $ventas
                ->groupBy(function ($venta) {
                    return Carbon::parse($venta->created_at)->format('Y-m-d');
                })
                ->map(function ($ventas, $fecha) {
                    return [
                        'periodo' => $fecha,
                        'total' => $ventas->sum('total'),
                        'cantidad_ventas' => $ventas->count(),
                    ];
                })
                ->values();
        } else {

            $serie = $ventas
                ->groupBy(function ($venta) {
                    return Carbon::parse($venta->created_at)->startOfWeek()->format('Y-m-d');
                })
                ->map(function ($ventas, $fecha) {
                    return [
                        'periodo' => $fecha,
                        'total' => $ventas->sum('total'),
                        'cantidad_ventas' => $ventas->count(),
                    ];
                })
                ->values();
        }

        $pdf = Pdf::loadView('reportes.resumen-ventas', [

            'fecha_inicio' => $fecha_inicio,
            'fecha_fin' => $fecha_fin,
            'total_vendido' => $total_vendido,
            'numero_ventas' => $numero_ventas,
            'ticket_promedio' => $ticket_promedio,
            'tipo_agrupacion' => $duracion <= 31 ? 'diaria' : 'semanal',
            'serie' => $serie,

        ]);

        return $pdf->stream('reporte-resumen-ventas.pdf');
    }

    public function ventasComparativa(Request $request)
    {
        $request->validate([
            'fecha_inicio_1' => 'required|date',
            'fecha_fin_1' => 'required|date|after_or_equal:fecha_inicio_1',

            'fecha_inicio_2' => 'required|date',
            'fecha_fin_2' => 'required|date|after_or_equal:fecha_inicio_2',
        ]);

        $inicio1 = Carbon::parse($request->fecha_inicio_1)->startOfDay();
        $fin1 = Carbon::parse($request->fecha_fin_1)->endOfDay();

        $inicio2 = Carbon::parse($request->fecha_inicio_2)->startOfDay();
        $fin2 = Carbon::parse($request->fecha_fin_2)->endOfDay();

        $ventas1 = Venta::where('estado', 'PROCESADA')
            ->whereBetween('created_at', [$inicio1, $fin1])
            ->get();

        $ventas2 = Venta::where('estado', 'PROCESADA')
            ->whereBetween('created_at', [$inicio2, $fin2])
            ->get();

        $total1 = $ventas1->sum('total');
        $total2 = $ventas2->sum('total');

        $cantidad1 = $ventas1->count();
        $cantidad2 = $ventas2->count();

        $promedio1 = $cantidad1 > 0 ? $total1 / $cantidad1 : 0;
        $promedio2 = $cantidad2 > 0 ? $total2 / $cantidad2 : 0;

        $variacion = $total1 != 0
            ? (($total2 - $total1) / $total1) * 100
            : 0;

        $dias1 = $inicio1->diffInDays($fin1) + 1;
        $dias2 = $inicio2->diffInDays($fin2) + 1;

        $pdf = Pdf::loadView('reportes.ventas-comparativa', [
            'inicio1' => $inicio1,
            'fin1' => $fin1,
            'inicio2' => $inicio2,
            'fin2' => $fin2,

            'total1' => $total1,
            'total2' => $total2,

            'cantidad1' => $cantidad1,
            'cantidad2' => $cantidad2,

            'promedio1' => $promedio1,
            'promedio2' => $promedio2,

            'variacion' => $variacion,

            'dias1' => $dias1,
            'dias2' => $dias2,
        ]);

        return $pdf->stream('reporte-ventas-comparativa.pdf');
    }
}

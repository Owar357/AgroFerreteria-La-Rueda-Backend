<?php

namespace App\Http\Controllers\Reportes\Ventas;

use App\Http\Controllers\Controller;
use App\Models\Venta;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;

class VentasComparativaReportController extends Controller
{
    public function __invoke(Request $request)
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

        $datos1 = Venta::where('estado', 'PROCESADA')
            ->whereBetween('created_at', [$inicio1, $fin1])
            ->selectRaw('SUM(total) as total, COUNT(*) as cantidad')
            ->first();

        $datos2 = Venta::where('estado', 'PROCESADA')
            ->whereBetween('created_at', [$inicio2, $fin2])
            ->selectRaw('SUM(total) as total, COUNT(*) as cantidad')
            ->first();

        $total1 = $datos1->total ?? 0;
        $total2 = $datos2->total ?? 0;

        $cantidad1 = $datos1->cantidad ?? 0;
        $cantidad2 = $datos2->cantidad ?? 0;

        $promedio1 = $cantidad1 > 0 ? $total1 / $cantidad1 : 0;
        $promedio2 = $cantidad2 > 0 ? $total2 / $cantidad2 : 0;

        $variacion = $total1 != 0
            ? (($total2 - $total1) / $total1) * 100
            : 0;

        $dias1 = $inicio1->startOfDay()->diffInDays($fin1->startOfDay()) + 1;
        $dias2 = $inicio2->startOfDay()->diffInDays($fin2->startOfDay()) + 1;

        $pdf = Pdf::loadView('reportes.ventas.ventas-comparativa', [
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

        return $pdf->stream("ventas-comparativa-del{$inicio1->format('Y-m-d')}_al_{$fin1->format('Y-m-d')}_vs_{$inicio2->format('Y-m-d')}_al_{$fin2->format('Y-m-d')}.pdf");
    }
}

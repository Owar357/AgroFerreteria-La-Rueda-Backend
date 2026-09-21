<?php

namespace App\Http\Controllers\Reportes\Ventas;

use App\Http\Controllers\Controller;
use App\Models\Venta;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ResumenVentasReportController extends Controller
{
    public function __invoke(Request $request)
    {
        $request->validate([
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'required|date|after_or_equal:fecha_inicio',
        ]);

        $fecha_inicio = Carbon::parse($request->fecha_inicio)->startOfDay();
        $fecha_fin = Carbon::parse($request->fecha_fin)->endOfDay();

        $query = Venta::where('estado', 'PROCESADA')
            ->whereBetween('created_at', [$fecha_inicio, $fecha_fin]);

        $total_vendido = (clone $query)->sum('total');
        $numero_ventas = (clone $query)->count();

        $ticket_promedio = $numero_ventas != 0
            ? $total_vendido / $numero_ventas
            : 0;

        $duracion = $fecha_inicio->diffInDays($fecha_fin) + 1;

       $formatoFecha = $duracion <= 31 ? 'YYYY-MM-DD' : 'IYYY-IW';


       $formatoFecha = $duracion <= 31 ? 'YYYY-MM-DD' : 'IYYY-IW';

        $serie = Venta::where('estado', 'PROCESADA')
            ->whereBetween('created_at', [$fecha_inicio, $fecha_fin])
            ->select(
                DB::raw("TO_CHAR(created_at, '$formatoFecha') as periodo"),
                DB::raw('SUM(total) as total'),
                DB::raw('COUNT(*) as cantidad_ventas')
            )
            ->groupBy('periodo')
            ->orderBy('periodo', 'asc')
            ->get();
            
        $pdf = Pdf::loadView('reportes.ventas.resumen-ventas', [
            'fecha_inicio' => $fecha_inicio,
            'fecha_fin' => $fecha_fin,
            'total_vendido' => $total_vendido,
            'numero_ventas' => $numero_ventas,
            'ticket_promedio' => $ticket_promedio,
            'tipo_agrupacion' => $duracion <= 31 ? 'diaria' : 'semanal',
            'serie' => $serie,
        ]);

        return $pdf->stream("resumen-ventas-{$fecha_inicio->format('Y-m-d')}_al_{$fecha_fin->format('Y-m-d')}.pdf");
    }
}
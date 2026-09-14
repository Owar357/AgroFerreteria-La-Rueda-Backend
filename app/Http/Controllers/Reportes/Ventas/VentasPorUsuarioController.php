<?php

namespace App\Http\Controllers\Reportes\Ventas;

use App\Http\Controllers\Controller;
use App\Models\Venta;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;

class VentasPorUsuarioController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request)
    {
        $request->validate([
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'required|date|after_or_equal:fecha_inicio',
        ]);


        $fecha_inicio = Carbon::parse($request->fecha_inicio)->startOfDay();
        $fecha_fin = Carbon::parse($request->fecha_fin)->endOfDay();

        $ventas = Venta::with('vendidoPor:id,name')
            ->where('estado', 'PROCESADA')
            ->whereBetween('created_at', [$fecha_inicio,$fecha_fin])
            ->latest()
            ->get();

        $resultado = [];

        
        foreach($ventas as $venta){
           
            $usuarioNombre = $venta->vendidoPor->name ?? 'N/A';

           if(!isset($resultado[$usuarioNombre])){
                  
                $resultado[$usuarioNombre] = [ 
                    'nombre' => $usuarioNombre,
                    'total_vendido' => 0,
                    'numero_ventas' => 0,
                    'ticket_promedio' => 0      
                ];
           }

              $resultado[$usuarioNombre]['total_vendido'] += $venta->total;
              $resultado[$usuarioNombre]['numero_ventas'] ++;
           }

        
        $resultado[$usuarioNombre]['ticket_promedio'] =  $resultado[$usuarioNombre]['total_vendido'] / $resultado[$usuarioNombre]['numero_ventas'];

    
        usort($resultado, function ($a, $b  ) {
            return $b['total_vendido'] <=> $a['total_vendido'];
        });


        $pdf = Pdf::loadView('reportes.ventas.ventas-por-usuario', [
            'resultado' => $resultado,
            'fecha_inicio' => $fecha_inicio,
            'fecha_fin' => $fecha_fin,
        ]);

        return $pdf->stream("reporte-ventas-por-usuario-{$fecha_inicio->format('Y-m-d')}_al_{$fecha_fin->format('Y-m-d')}.pdf");
    }
}

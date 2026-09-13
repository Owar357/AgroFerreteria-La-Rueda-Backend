<?php

namespace App\Http\Controllers\Reportes\Caja;

use App\Http\Controllers\Controller;
use App\Models\AperturaVenta;
use App\Models\Venta;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class ArqueoCajaReporteController extends Controller
{
    /**
     * Genera el PDF para el reporte de arqueo de caja filtrando por Fecha y Cajero.
     */
    public function __invoke(Request $request)
    {

        $request->validate([
            'fecha'     => 'required|date',
            'cajero_id' => 'required|exists:users,id',
        ]);

        $fecha = $request->input('fecha');
        $cajeroId = $request->input('cajero_id');

         
        
        $apertura = AperturaVenta::with('cajero:id,name')
            ->where('cajero_id', $cajeroId)
            ->whereDate('fecha_hora_apertura', $fecha)
            ->latest('fecha_hora_apertura')
            ->first();


        $nombreCajero = $apertura->cajero?->name?? "cajero-{$cajeroId}";

        $nombreLimpio = \Illuminate\Support\Str::slug($nombreCajero);

        if (!$apertura) {
            return response()->json([
                'status'  => 'error',
                'message' => 'No se encontró ninguna apertura de caja para el cajero y fecha seleccionados.'
            ], 404);
        }

    
        $ventasEfectivo = Venta::where('apertura_venta_id', $apertura->id)
            ->where('tipo_pago', 'EFECTIVO')
            ->where('estado', '!=', 'ANULADA')
            ->sum('total');

        $montoEsperado = $apertura->monto_inicial + $ventasEfectivo;
        $montoContado  = $apertura->monto_contado ?? 0;
        $diferencia    = $montoContado - $montoEsperado;

        if ($diferencia > 0) {
            $estadoArqueo = 'SOBRANTE';
        } elseif ($diferencia < 0) {
            $estadoArqueo = 'FALTANTE';
        } else {
            $estadoArqueo = 'CUADRADO';
        }

        $resultado = [
            'cajero'          => $apertura->cajero?->name ?? 'N/A',
            'fecha_apertura'  => $apertura->fecha_hora_apertura,
            'fecha_cierre'    => $apertura->fecha_hora_cierre,
            'monto_inicial'   => $apertura->monto_inicial,
            'ventas_efectivo' => $ventasEfectivo,
            'monto_esperado'  => $montoEsperado,
            'monto_contado'   => $montoContado,
            'diferencia'      => $diferencia,
            'estado_arqueo'   => $estadoArqueo
        ];

        $pdf = Pdf::loadView('reportes.caja.arqueo-caja', compact('resultado'));

        return $pdf->stream("reporte-arqueo-{$fecha}-{$nombreLimpio}.pdf");
    }
}

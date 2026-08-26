<?php

namespace App\Http\Controllers\Reportes;

use App\Http\Controllers\Controller;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;

class ReporteCajaController extends Controller
{
    public function arqueoCaja($apertura_venta_id)
    {
        $apertura = DB::table('apertura_ventas')
            ->join('users', 'apertura_ventas.cajero_id', '=', 'users.id')
            ->where('apertura_ventas.id', $apertura_venta_id)
            ->select(
                'apertura_ventas.*',
                'users.name as cajero'
            )
            ->first();

        if (!$apertura) {
            return response()->json([
                'status' => 'error',
                'message' => 'No se encontró la apertura de venta.'
            ], 404);
        }

        $ventasEfectivo = DB::table('ventas')
            ->where('apertura_venta_id', $apertura_venta_id)
            ->where('tipo_pago', 'EFECTIVO')
            ->where('estado', '!=', 'ANULADA')
            ->sum('total');

        $montoEsperado = $apertura->monto_inicial + $ventasEfectivo;

        $montoContado = $apertura->monto_contado ?? 0;

        $diferencia = $montoContado - $montoEsperado;

        if ($diferencia > 0) {
            $estadoArqueo = 'SOBRANTE';
        } elseif ($diferencia < 0) {
            $estadoArqueo = 'FALTANTE';
        } else {
            $estadoArqueo = 'CUADRADO';
        }

        $resultado = [
            'cajero' => $apertura->cajero,
            'fecha_apertura' => $apertura->fecha_hora_apertura,
            'fecha_cierre' => $apertura->fecha_hora_cierre,
            'monto_inicial' => $apertura->monto_inicial,
            'ventas_efectivo' => $ventasEfectivo,
            'monto_esperado' => $montoEsperado,
            'monto_contado' => $montoContado,
            'diferencia' => $diferencia,
            'estado_arqueo' => $estadoArqueo
        ];

        $pdf = Pdf::loadView('reportes.arqueo-caja', [
            'resultado' => $resultado
        ]);

        return $pdf->stream('reporte-arqueo-caja.pdf');
    }
}

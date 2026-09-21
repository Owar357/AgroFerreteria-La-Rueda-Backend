<?php

namespace App\Http\Controllers\Reportes\Ventas;

use App\Http\Controllers\Controller;
use App\Models\Venta;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ProductosMasVendidosController extends Controller
{
    public function __invoke(Request $request)
    {
        $request->validate([
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'required|date|after_or_equal:fecha_inicio',
            'limite' => 'nullable|integer|min:1',
        ]);

        $limite = $request->limite ?? 10;
        $fecha_inicio = Carbon::parse($request->fecha_inicio)->startOfDay();
        $fecha_final = Carbon::parse($request->fecha_fin)->endOfDay();

        $ventas = Venta::with([
                'detallesVenta.loteDetallesVenta:id,detalle_venta_id,cantidad_tomada'
            ])
            ->where('estado', 'PROCESADA')
            ->whereBetween('created_at', [$fecha_inicio, $fecha_final])
            ->get();

        $resultado = [];

        foreach ($ventas as $venta) {
            foreach ($venta->detallesVenta as $detalle) {
                $producto = $detalle->nombre_producto;

                if (! isset($resultado[$producto])) {
                    $resultado[$producto] = [
                        'producto' => $producto,
                        'unidades_vendidas' => 0,
                        'monto_total' => 0,
                        'numero_ventas' => 0,
                    ];
                }

                // Sumamos los lotes asociados a este detalle de venta
                $cantidadLotes = $detalle->loteDetallesVenta->sum('cantidad_tomada');
                $cantidad = $cantidadLotes > 0 ? $cantidadLotes : $detalle->cantidad;
                $monto = $cantidad * $detalle->precio_unitario;

                $resultado[$producto]['unidades_vendidas'] += $cantidad;
                $resultado[$producto]['monto_total'] += $monto;
                $resultado[$producto]['numero_ventas']++;
            }
        }

        usort($resultado, function ($a, $b) {
            return $b['unidades_vendidas'] <=> $a['unidades_vendidas'];
        });

        $resultado = array_slice($resultado, 0, $limite);

        $pdf = Pdf::loadView('reportes.ventas.productos-mas-vendidos', [
            'resultado' => $resultado,
            'fecha_inicio' => $request->fecha_inicio,
            'fecha_fin' => $request->fecha_fin,
        ]);

        $nombreArchivo = "reporte-productos-mas-vendidos-{$fecha_inicio->format('Y-m-d')}_al_{$fecha_final->format('Y-m-d')}.pdf";

        return $pdf->stream($nombreArchivo);
    }
}
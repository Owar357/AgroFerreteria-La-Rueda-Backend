<?php

namespace App\Http\Controllers\Reportes\Ventas;

use App\Http\Controllers\Controller;
use App\Models\Venta;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;

class VentasPorCategoriaController extends Controller
{
    public function __invoke(Request $request)
    {
        $request->validate([
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'required|date|after_or_equal:fecha_inicio',
            'categoria_id' => 'nullable|exists:categorias,id',
        ]);

        $fecha_inicio = Carbon::parse($request->fecha_inicio)->startOfDay();
        $fecha_fin = Carbon::parse($request->fecha_fin)->endOfDay();

        $ventas = Venta::with([
                'detallesVenta.loteDetallesVenta.lote.presentacion.producto.categoria'
            ])
            ->when($request->categoria_id, function ($query) use ($request) {
                $query->whereHas('detallesVenta.loteDetallesVenta.lote.presentacion.producto', function ($q) use ($request) {
                    $q->where('categoria_id', $request->categoria_id);
                });
            })
            ->where('estado', 'PROCESADA')
            ->whereBetween('created_at', [$fecha_inicio, $fecha_fin])
            ->get();

        $resultado = [];
        $totalGeneral = 0;

        foreach ($ventas as $venta) {
            foreach ($venta->detallesVenta as $detalle) {
                
                $lotes = $detalle->loteDetallesVenta;

                if (! $lotes || $lotes->isEmpty()) {
                    continue;
                }

                foreach ($lotes as $loteDetalle) {
                    $producto = optional(optional(optional($loteDetalle->lote)->presentacion))->producto;

                    if (! $producto) {
                        continue;
                    }

                    $categoria = $producto->categoria;
                    $nombreCategoria = $categoria ? $categoria->nombre : 'Sin clasificar';

                    if (! isset($resultado[$nombreCategoria])) {
                        $resultado[$nombreCategoria] = [
                            'categoria' => $nombreCategoria,
                            'total_vendido' => 0,
                            'cantidad_unidades' => 0,
                        ];
                    }

                    $cantidad = $loteDetalle->cantidad_tomada;
                    $subtotalVenta = $cantidad * $detalle->precio_unitario;

                    $resultado[$nombreCategoria]['cantidad_unidades'] += $cantidad;
                    $resultado[$nombreCategoria]['total_vendido'] += $subtotalVenta;

                    $totalGeneral += $subtotalVenta;
                }
            }
        }

        foreach ($resultado as &$categoria) {
            $categoria['porcentaje_total'] = $totalGeneral > 0
                ? round(($categoria['total_vendido'] / $totalGeneral) * 100, 2)
                : 0;

            $categoria['total_vendido'] = round($categoria['total_vendido'], 2);
        }
        unset($categoria);

        usort($resultado, function ($a, $b) {
            return $b['total_vendido'] <=> $a['total_vendido'];
        });

        $pdf = Pdf::loadView('reportes.ventas.ventas-por-categoria', [
            'resultado' => $resultado,
            'fecha_inicio' => $request->fecha_inicio,
            'fecha_fin' => $request->fecha_fin,
        ]);

        return $pdf->stream("reporte-ventas-por-categoria-del-{$fecha_inicio->format('Y-m-d')}_al_{$fecha_fin->format('Y-m-d')}.pdf");
    }
}
<?php

namespace App\Http\Controllers\Reportes\Financieros;

use App\Http\Controllers\Controller;
use App\Models\Venta;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;

class MargenGananciaReporteController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request)
    {
        $request->validate([
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'required|date|after_or_equal:fecha_inicio',
            'presentacion_id' => 'nullable|exists:presentaciones,id',
            'categoria_id' => 'nullable|exists:categorias,id',
        ]);

        $fecha_inicio = Carbon::parse($request->fecha_inicio)->startOfDay();
        $fecha_fin = Carbon::parse($request->fecha_fin)->endOfDay();

        $ventas = Venta::with([
            'detallesVenta.loteDetallesVenta.lote.presentacion.producto',
            'detallesVenta.loteDetallesVenta.lote.producto',
        ])
            ->where('estado', 'PROCESADA')
            ->whereBetween('created_at', [$fecha_inicio, $fecha_fin])
            ->get();

        $resultado = [];

        foreach ($ventas as $venta) {
            foreach ($venta->detallesVenta as $detalle) {
                $lotesDetalle = $detalle->loteDetallesVenta;

                if ($lotesDetalle->isEmpty()) {
                    continue;
                }

            
                $totalBaseUnidadesDetalle = $lotesDetalle->sum('cantidad_tomada');
                
              
                $montoTotalDetalle = $detalle->cantidad * $detalle->precio_unitario;

                foreach ($lotesDetalle as $loteDetalle) {
                    $lote = $loteDetalle->lote;
                    if (! $lote) {
                        continue;
                    }

                    $presentacion = $lote->presentacion;
                    $productoModelo = $lote->producto ?? ($presentacion ? $presentacion->producto : null);

                    if (! $productoModelo) {
                        continue;
                    }

                    if (
                        $request->filled('presentacion_id') &&
                        optional($presentacion)->id != $request->presentacion_id
                    ) {
                        continue;
                    }

                    if (
                        $request->filled('categoria_id') &&
                        $productoModelo->categoria_id != $request->categoria_id
                    ) {
                        continue;
                    }

                    $producto = $detalle->nombre_producto;

                    if (! isset($resultado[$producto])) {
                        $resultado[$producto] = [
                            'producto' => $producto,
                            'cantidad_total' => 0,
                            'venta_total' => 0,
                            'costo_total' => 0,
                        ];
                    }

                    $cantidadBase = $loteDetalle->cantidad_tomada;
                    
                    
                    $factorConversion = $lote->presentacion->factor_conversion ?? 1;

                    $precioVentaBase = $detalle->precio_unitario / $factorConversion;

                    $proporcion = $totalBaseUnidadesDetalle > 0 ? ($cantidadBase / $totalBaseUnidadesDetalle) : 1;
                    $ventaLote = ($detalle->cantidad * $precioVentaBase) * $proporcion;
                    
                    $costoLote = $cantidadBase * $lote->costo_unitario_compra;

                    $resultado[$producto]['cantidad_total'] += $cantidadBase;
                    $resultado[$producto]['venta_total'] += $ventaLote;
                    $resultado[$producto]['costo_total'] += $costoLote;
                }
            }
        }

        foreach ($resultado as &$producto) {
            $producto['precio_venta_promedio'] = $producto['cantidad_total'] != 0
                ? round($producto['venta_total'] / $producto['cantidad_total'], 3)
                : 0;

            $producto['costo_promedio_ponderado'] = $producto['cantidad_total'] != 0
                ? round($producto['costo_total'] / $producto['cantidad_total'], 3)
                : 0;

            $producto['margen_absoluto'] = round(
                $producto['precio_venta_promedio'] - $producto['costo_promedio_ponderado'],
                3
            );

            $producto['margen_porcentual'] = $producto['precio_venta_promedio'] != 0
                ? round(($producto['margen_absoluto'] / $producto['precio_venta_promedio']) * 100, 3)
                : 0;

            unset(
                $producto['cantidad_total'],
                $producto['venta_total'],
                $producto['costo_total']
            );
        }
        unset($producto);

        $resultado = array_values($resultado);

        $pdf = Pdf::loadView('reportes.financieros.margen-ganancia', [
            'resultado' => $resultado,
            'fecha_inicio' => $request->fecha_inicio,
            'fecha_fin' => $request->fecha_fin,
        ]);

        return $pdf->stream("reporte-margen-ganancia-del-{$fecha_inicio->format('Y-m-d')}_al-{$fecha_fin->format('Y-m-d')}.pdf");
    }
}

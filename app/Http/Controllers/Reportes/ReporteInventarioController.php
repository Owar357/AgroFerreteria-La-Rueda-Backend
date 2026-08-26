<?php

namespace App\Http\Controllers\Reportes;

use App\Http\Controllers\Controller;
use App\Models\Producto;
use App\Models\Lote;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReporteInventarioController extends Controller
{
    public function inventarioValorizado(Request $request)
    {
        $productos = Producto::join('presentaciones', 'productos.id', '=', 'presentaciones.producto_id')
            ->join('lotes', 'presentaciones.id', '=', 'lotes.presentacion_id')
            ->where('lotes.cantidad_actual', '>', 0)
            ->where('lotes.estado', 'ACTIVO')
            ->select(
                'productos.id',
                'productos.nombre',
                DB::raw('SUM(lotes.cantidad_actual) as cantidad_stock'),
                DB::raw('SUM(lotes.cantidad_actual * lotes.costo_unitario_compra) / SUM(lotes.cantidad_actual) as costo_promedio'),
                DB::raw('SUM(lotes.cantidad_actual * lotes.costo_unitario_compra) as valor_costo'),
                DB::raw('SUM(lotes.cantidad_actual * presentaciones.precio_venta) as valor_venta')
            )
            ->groupBy(
                'productos.id',
                'productos.nombre'
            )
            ->get();

        $totalStock = $productos->sum('cantidad_stock');
        $totalCosto = $productos->sum('valor_costo');
        $totalVenta = $productos->sum('valor_venta');

        $fecha_corte = now();

        $pdf = Pdf::loadView('reportes.inventario-valorizado', [
            'resultado' => $productos,
            'totalStock' => $totalStock,
            'totalCosto' => $totalCosto,
            'totalVenta' => $totalVenta,
            'fecha_corte' => $fecha_corte
        ]);

        return $pdf->stream('inventario-valorizado.pdf');
    }

    public function productosPorVencer(Request $request)
    {
        $request->validate([
            'dias_umbral' => 'nullable|integer|min:1',
        ]);

        $dias_umbral = (int) ($request->dias_umbral ?? 30);

        $hoy = now()->startOfDay();
        $fecha_limite = now()->addDays($dias_umbral)->endOfDay();

        $lotes = Lote::join('presentaciones', 'lotes.presentacion_id', '=', 'presentaciones.id')
            ->join('productos', 'presentaciones.producto_id', '=', 'productos.id')
            ->where('lotes.cantidad_actual', '>', 0)
            ->where('lotes.estado', 'ACTIVO')
            ->whereNotNull('lotes.fecha_vencimiento')
            ->whereBetween('lotes.fecha_vencimiento', [
                $hoy->toDateString(),
                $fecha_limite->toDateString()
            ])
            ->select(
                'lotes.id',
                'lotes.lote_interno',
                'lotes.fecha_vencimiento',
                'lotes.cantidad_actual',
                'productos.nombre as producto',
                'presentaciones.nombre as presentacion'
            )
            ->orderBy('lotes.fecha_vencimiento', 'asc')
            ->get();

        $pdf = Pdf::loadView('reportes.productos-por-vencer', [
            'resultado' => $lotes,
            'dias_umbral' => $dias_umbral,
            'fecha_corte' => now()
        ]);

        return $pdf->stream('productos-por-vencer.pdf');
    }
}

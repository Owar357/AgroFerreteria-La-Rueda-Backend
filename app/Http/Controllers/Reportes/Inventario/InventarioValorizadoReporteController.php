<?php

namespace App\Http\Controllers\Reportes\Inventario;

use App\Http\Controllers\Controller;
use App\Models\Producto;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InventarioValorizadoReporteController extends Controller
{
    public function __invoke(Request $request)
    {
        $request->validate([
            'categoria_id' => 'nullable|exists:categorias,id',
        ]);

        $categoriaId = $request->input('categoria_id');
        $categoriaNombre = null;

        if ($categoriaId) {
            $categoriaNombre = DB::table('categorias')->where('id', $categoriaId)->value('nombre');
        }
        
        $presentacionBaseGranel = DB::table('presentaciones')
            ->select('producto_id', 'precio_venta', 'factor_conversion')
            ->where('activo', true)
            ->orderBy('producto_id')
            ->orderByDesc('es_base')
            ->orderBy('id');

        $subPresentacionBase = DB::table(DB::raw("({$presentacionBaseGranel->toSql()}) as pres_base"))
            ->mergeBindings($presentacionBaseGranel)
            ->select('producto_id', 'precio_venta', 'factor_conversion')
            ->distinct('producto_id');

        $formulaCostoLote = "lotes.costo_unitario_compra * (1 - COALESCE(lotes.porcentaje_descuento, 0) / 100)";

        // 1. GRANEL
        $queryGranel = Producto::join('lotes', 'productos.id', '=', 'lotes.producto_id')
            ->join('unidad_medidas', 'productos.unidad_medida_id', '=', 'unidad_medidas.id')
            ->leftJoinSub($subPresentacionBase, 'pres', function ($join) {
                $join->on('productos.id', '=', 'pres.producto_id');
            })
            ->where('productos.tipo_producto', '=', 'GRANEL')
            ->where('lotes.cantidad_actual', '>', 0)
            ->where('lotes.estado', '=', 'ACTIVO')
            ->when($categoriaId, fn($q) => $q->where('productos.categoria_id', $categoriaId))
            ->select(
                'productos.id',
                'productos.codigo',
                'productos.nombre as producto_nombre',
                DB::raw("NULL as presentacion_nombre"),
                'unidad_medidas.abreviatura as unidad_medida',
                DB::raw('MIN(pres.precio_venta / NULLIF(pres.factor_conversion, 0)) as precio_venta_unitario'),
                DB::raw('SUM(lotes.cantidad_actual) as cantidad_stock'),
                DB::raw("SUM(lotes.cantidad_actual * {$formulaCostoLote}) / NULLIF(SUM(lotes.cantidad_actual), 0) as costo_promedio"),
                DB::raw("SUM(lotes.cantidad_actual * {$formulaCostoLote}) as valor_costo"),
                DB::raw('SUM((lotes.cantidad_actual / NULLIF(pres.factor_conversion, 0)) * pres.precio_venta) as valor_venta')
            )
            ->groupBy('productos.id', 'productos.codigo', 'productos.nombre', 'unidad_medidas.abreviatura');

        // 2. UNIDAD FIJA
        $queryUnidadFija = Producto::join('presentaciones', 'productos.id', '=', 'presentaciones.producto_id')
            ->join('lotes', 'presentaciones.id', '=', 'lotes.presentacion_id')
            ->join('unidad_medidas', 'presentaciones.unidad_medida_id', '=', 'unidad_medidas.id')
            ->where('productos.tipo_producto', '=', 'UNIDAD FIJA')
            ->where('lotes.cantidad_actual', '>', 0)
            ->where('lotes.estado', '=', 'ACTIVO')
            ->when($categoriaId, fn($q) => $q->where('productos.categoria_id', $categoriaId))
            ->select(
                'productos.id',
                'productos.codigo',
                'productos.nombre as producto_nombre',
                'presentaciones.nombre as presentacion_nombre',
                'unidad_medidas.abreviatura as unidad_medida',
                'presentaciones.precio_venta as precio_venta_unitario',
                DB::raw('SUM(lotes.cantidad_actual) as cantidad_stock'),
                DB::raw("SUM(lotes.cantidad_actual * {$formulaCostoLote}) / NULLIF(SUM(lotes.cantidad_actual), 0) as costo_promedio"),
                DB::raw("SUM(lotes.cantidad_actual * {$formulaCostoLote}) as valor_costo"),
                DB::raw('SUM(lotes.cantidad_actual * presentaciones.precio_venta) as valor_venta')
            )
            ->groupBy('productos.id', 'productos.codigo', 'productos.nombre', 'presentaciones.id', 'presentaciones.nombre', 'presentaciones.precio_venta', 'unidad_medidas.abreviatura');

        $resultado = $queryGranel->unionAll($queryUnidadFija)->get()->sortBy('producto_nombre');

        $totalStock = $resultado->sum('cantidad_stock');
        $totalCosto = $resultado->sum('valor_costo');
        $totalVenta = $resultado->sum('valor_venta');
        $totalGanancia = $totalVenta - $totalCosto;
        $margenGlobal = $totalVenta > 0 ? ($totalGanancia / $totalVenta) * 100 : 0;
        
        $fechaEmision = now();

        $pdf = Pdf::loadView('reportes.inventario.inventario-valorizado', [
            'productos'       => $resultado,
            'totalStock'      => $totalStock,
            'totalCosto'      => $totalCosto,
            'totalVenta'      => $totalVenta,
            'totalGanancia'   => $totalGanancia,
            'margenGlobal'    => $margenGlobal,
            'fechaEmision'    => $fechaEmision,
            'categoriaNombre' => $categoriaNombre,
        ]);

        return $pdf->stream("inventario-valorizado-{$fechaEmision->format('Y-m-d')}.pdf");
    }
}
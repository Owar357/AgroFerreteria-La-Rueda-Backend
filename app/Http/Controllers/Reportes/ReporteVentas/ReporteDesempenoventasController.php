<?php

namespace App\Http\Controllers\Reportes\ReporteVentas;

use App\Http\Controllers\Controller;
use App\Models\LoteDetalleVenta;
use App\Models\Producto;
use App\Models\Venta;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReporteDesempenoVentasController extends Controller
{
    public function ventasPorUsuarioPdf(Request $request)
    {
        $request->validate([
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'required|date|after_or_equal:fecha_inicio',
        ]);

        $ventas = Venta::where('estado', 'PROCESADA')
            ->whereBetween('created_at', [
                Carbon::parse($request->fecha_inicio)->startOfDay(),
                Carbon::parse($request->fecha_fin)->endOfDay()
            ])
            ->with('vendidoPor')
            ->get();

        $resultado = [];

        foreach ($ventas as $venta) {

            $usuario = $venta->vendidoPor;

            $nombre = $usuario ? $usuario->name : 'Usuario eliminado';

            if (!isset($resultado[$nombre])) {
                $resultado[$nombre] = [
                    'usuario' => $nombre,
                    'total_vendido' => 0,
                    'numero_ventas' => 0,
                    'ticket_promedio' => 0,
                ];
            }

            $resultado[$nombre]['total_vendido'] += $venta->total;
            $resultado[$nombre]['numero_ventas']++;
        }

        foreach ($resultado as &$usuario) {

            $usuario['ticket_promedio'] = $usuario['numero_ventas'] > 0
                ? $usuario['total_vendido'] / $usuario['numero_ventas']
                : 0;
        }

        usort($resultado, function ($a, $b) {
            return $b['total_vendido'] <=> $a['total_vendido'];
        });

        $pdf = Pdf::loadView('reportes.ventas-por-usuario', [
            'resultado' => $resultado,
            'fecha_inicio' => $request->fecha_inicio,
            'fecha_fin' => $request->fecha_fin,
        ]);

        return $pdf->stream('reporte-ventas-por-usuario.pdf');
    }

    public function ventasPorCategoria(Request $request)
    {
        $request->validate([
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'required|date|after_or_equal:fecha_inicio',
        ]);

        $ventas = Venta::with('detallesVenta')
            ->where('estado', 'PROCESADA')
            ->whereBetween('created_at', [
                Carbon::parse($request->fecha_inicio)->startOfDay(),
                Carbon::parse($request->fecha_fin)->endOfDay()
            ])
            ->get();

        $resultado = [];

        foreach ($ventas as $venta) {

            foreach ($venta->detallesVenta as $detalle) {

                $lotes = LoteDetalleVenta::with('lote.presentacion.producto')
                    ->where('detalle_venta_id', $detalle->id)
                    ->get();

                foreach ($lotes as $loteDetalle) {

                    $producto = $loteDetalle->lote->presentacion->producto;
                    $categoria = $producto->categoria;

                    $nombreCategoria = $categoria
                        ? $categoria->nombre
                        : 'Sin clasificar';

                    if (!isset($resultado[$nombreCategoria])) {
                        $resultado[$nombreCategoria] = [
                            'categoria' => $nombreCategoria,
                            'total_vendido' => 0,
                            'cantidad_unidades' => 0,
                        ];
                    }

                    $cantidad = $loteDetalle->cantidad_tomada;
                    $precioVenta = $detalle->precio_unitario;

                    $resultado[$nombreCategoria]['cantidad_unidades'] += $cantidad;
                    $resultado[$nombreCategoria]['total_vendido'] +=
                        $cantidad * $precioVenta;
                }
            }
        }

        $totalGeneral = array_sum(
            array_column($resultado, 'total_vendido')
        );

        foreach ($resultado as &$categoria) {

            $categoria['porcentaje_total'] = $totalGeneral != 0
                ? round(
                    ($categoria['total_vendido'] / $totalGeneral) * 100,
                    3
                )
                : 0;

            $categoria['total_vendido'] = round(
                $categoria['total_vendido'],
                3
            );
        }

        usort($resultado, function ($a, $b) {
            return $b['total_vendido'] <=> $a['total_vendido'];
        });

        $pdf = Pdf::loadView('reportes.ventas-por-categoria', [
            'resultado' => $resultado,
            'fecha_inicio' => $request->fecha_inicio,
            'fecha_fin' => $request->fecha_fin,
        ]);

        return $pdf->stream('reporte-ventas-por-categoria.pdf');
    }

    public function productosMasVendidosPdf(Request $request)
    {
        $request->validate([
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'required|date|after_or_equal:fecha_inicio',
            'limite' => 'nullable|integer|min:1',
        ]);

        $limite = $request->limite ?? 10;

        $ventas = Venta::with('detallesVenta')
            ->where('estado', 'PROCESADA')
            ->whereBetween('created_at', [
                Carbon::parse($request->fecha_inicio)->startOfDay(),
                Carbon::parse($request->fecha_fin)->endOfDay()
            ])
            ->get();

        $resultado = [];

        foreach ($ventas as $venta) {

            foreach ($venta->detallesVenta as $detalle) {

                $lotes = LoteDetalleVenta::where(
                    'detalle_venta_id',
                    $detalle->id
                )->get();

                foreach ($lotes as $loteDetalle) {

                    $producto = $detalle->nombre_producto;
                    $cantidad = $loteDetalle->cantidad_tomada;
                    $monto = $cantidad * $detalle->precio_unitario;

                    if (!isset($resultado[$producto])) {
                        $resultado[$producto] = [
                            'producto' => $producto,
                            'unidades_vendidas' => 0,
                            'monto_total' => 0,
                            'numero_ventas' => 0,
                        ];
                    }

                    $resultado[$producto]['unidades_vendidas'] += $cantidad;
                    $resultado[$producto]['monto_total'] += $monto;
                }

                if (isset($resultado[$producto])) {
                    $resultado[$producto]['numero_ventas']++;
                }
            }
        }

        usort($resultado, function ($a, $b) {
            return $b['unidades_vendidas'] <=> $a['unidades_vendidas'];
        });

        $resultado = array_slice($resultado, 0, $limite);

        $pdf = Pdf::loadView('reportes.productos-mas-vendidos', [
            'resultado' => $resultado,
            'fecha_inicio' => $request->fecha_inicio,
            'fecha_fin' => $request->fecha_fin,
        ]);

        return $pdf->stream('reporte-productos-mas-vendidos.pdf');
    }

    public function productosMenosVendidos(Request $request)
    {
        $request->validate([
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'required|date|after_or_equal:fecha_inicio',
            'limite' => 'nullable|integer|min:1',
        ]);

        $fecha_inicio = $request->fecha_inicio;
        $fecha_fin = $request->fecha_fin;
        $limite = $request->limite ?? 20;

        $resultado = Producto::join('detalles_venta', function ($join) use ($fecha_inicio, $fecha_fin) {

            $join->on('productos.nombre', '=', 'detalles_venta.nombre_producto')
                ->whereBetween('detalles_venta.created_at', [
                    $fecha_inicio . ' 00:00:00',
                    $fecha_fin . ' 23:59:59'
                ]);
        })

            ->join('ventas', function ($join) {

                $join->on('detalles_venta.venta_id', '=', 'ventas.id')
                    ->where('ventas.estado', '!=', 'ANULADA');
            })

            ->select(
                'productos.id',
                'productos.nombre',
                DB::raw('SUM(detalles_venta.cantidad) as unidades_vendidas'),
                DB::raw('SUM(detalles_venta.subtotal) as monto_total'),
                DB::raw('COUNT(DISTINCT ventas.id) as numero_transacciones')
            )

            ->groupBy(
                'productos.id',
                'productos.nombre'
            )

            ->orderBy('unidades_vendidas', 'asc')
            ->limit($limite)
            ->get();

        $pdf = Pdf::loadView('reportes.productos-menos-vendidos', [
            'resultado' => $resultado,
            'fecha_inicio' => $fecha_inicio,
            'fecha_fin' => $fecha_fin,
        ]);

        return $pdf->stream('reporte-productos-menos-vendidos.pdf');
    }
}

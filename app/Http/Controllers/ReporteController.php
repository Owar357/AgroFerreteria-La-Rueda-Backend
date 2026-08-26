<?php

namespace App\Http\Controllers;

use App\Models\Compra;
use App\Models\LoteDetalleVenta;
use App\Models\Venta;
use App\Models\Categoria;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ReporteController extends Controller
{
    public function ventas(Request $request)
    {
        $ventas = Venta::select(
            'numero_factura',
            'created_at',
            'tipo_pago',
            'gravado',
            'iva',
            'total'
        );

        $fecha_desde = null;
        $fecha_hasta = null;

        if ($request->filled('fecha_desde') && $request->filled('fecha_hasta')) {

            $ventas->whereBetween('created_at', [$request->fecha_desde, $request->fecha_hasta]);
        }

        // Traemos los datos de la Db
        $ventas = $ventas->orderBy('created_at', 'asc')->get();

        $pdf = Pdf::loadView('reportes.ventas', [

            'ventas' => $ventas,
            'fecha_desde' => $request->fecha_desde,
            'fecha_hasta' => $request->fecha_hasta,

        ]);

        return $pdf->stream('reporte.pdf');
    }

    public function ticket($id)
    {
        $venta = Venta::with([
            'cliente',
            'vendidoPor',
            'detallesVenta',
        ])->findOrFail($id);

        $pdf = Pdf::loadView('reportes.ticket', compact('venta'))
            ->setPaper([0, 0, 240.77, 900], 'portrait');

        return $pdf->stream('Ticket-' . $venta->numero_factura . '.pdf');
    }

    public function flujoComprasVentas(Request $request)
    {
        $fecha_desde = Carbon::parse($request->fecha_desde);
        $fecha_hasta = Carbon::parse($request->fecha_hasta);

        $duracion = $fecha_desde->diffInDays($fecha_hasta) + 1;

        $fecha_anterior_hasta = $fecha_desde->copy()->subDay();
        $fecha_anterior_desde = $fecha_anterior_hasta->copy()->subDays($duracion - 1);

        $compras_actual = Compra::where('es_anulado', false)
            ->whereBetween('fecha_emision', [$fecha_desde, $fecha_hasta])
            ->sum('monto_total');

        $compras_anterior = Compra::where('es_anulado', false)
            ->whereBetween('fecha_emision', [$fecha_anterior_desde, $fecha_anterior_hasta])
            ->sum('monto_total');

        $ventas_actual = Venta::where('estado', 'PROCESADA')
            ->whereBetween('created_at', [$fecha_desde, $fecha_hasta])
            ->sum('total');

        $ventas_anterior = Venta::where('estado', 'PROCESADA')
            ->whereBetween('created_at', [$fecha_anterior_desde, $fecha_anterior_hasta])
            ->sum('total');

        $flujo_actual = $ventas_actual - $compras_actual;

        $flujo_anterior = $ventas_anterior - $compras_anterior;

        $variacion_compras = $compras_anterior != 0
            ? (($compras_actual - $compras_anterior) / $compras_anterior) * 100
            : 0;

        $variacion_ventas = $ventas_anterior != 0
            ? (($ventas_actual - $ventas_anterior) / $ventas_anterior) * 100
            : 0;

        $variacion_flujo = $flujo_anterior != 0
            ? (($flujo_actual - $flujo_anterior) / $flujo_anterior) * 100
            : 0;

        $pdf = Pdf::loadView('reportes.flujo-compras-ventas', [

            'fecha_desde' => $fecha_desde,
            'fecha_hasta' => $fecha_hasta,

            'fecha_anterior_desde' => $fecha_anterior_desde,
            'fecha_anterior_hasta' => $fecha_anterior_hasta,

            'compras_actual' => $compras_actual,
            'compras_anterior' => $compras_anterior,

            'ventas_actual' => $ventas_actual,
            'ventas_anterior' => $ventas_anterior,

            'flujo_actual' => $flujo_actual,
            'flujo_anterior' => $flujo_anterior,

            'variacion_compras' => $variacion_compras,
            'variacion_ventas' => $variacion_ventas,
            'variacion_flujo' => $variacion_flujo,

        ]);

        return $pdf->stream('reporte-flujo-compras-ventas.pdf');
    }

    public function margenGanancia(Request $request)
    {
        $request->validate([
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'required|date|after_or_equal:fecha_inicio',
            'presentacion_id' => 'nullable|exists:presentaciones,id',
            'categoria_id' => 'nullable|exists:categorias,id',
        ]);

        $ventas = Venta::with('detallesVenta')
            ->where('estado', 'PROCESADA')
            ->whereBetween('created_at', [
                Carbon::parse($request->fecha_inicio)->startOfDay(),
                Carbon::parse($request->fecha_fin)->endOfDay(),
            ])
            ->get();

        $resultado = [];

        foreach ($ventas as $venta) {

            foreach ($venta->detallesVenta as $detalle) {

                $lotes = LoteDetalleVenta::with('lote.presentacion.producto')
                    ->where('detalle_venta_id', $detalle->id)
                    ->get();

                foreach ($lotes as $loteDetalle) {

                    $presentacion = $loteDetalle->lote->presentacion;
                    $productoModelo = $presentacion->producto;

                    if (
                        $request->filled('presentacion_id') &&
                        $presentacion->id != $request->presentacion_id
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

                    $cantidad = $loteDetalle->cantidad_tomada;
                    $precioVenta = $detalle->precio_unitario;
                    $costoLote = $loteDetalle->lote->costo_unitario_compra;

                    $resultado[$producto]['cantidad_total'] += $cantidad;
                    $resultado[$producto]['venta_total'] += $cantidad * $precioVenta;
                    $resultado[$producto]['costo_total'] += $cantidad * $costoLote;
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
                $producto['precio_venta_promedio'] -
                    $producto['costo_promedio_ponderado'],
                3
            );

            $producto['margen_porcentual'] =
                $producto['precio_venta_promedio'] != 0
                ? round(
                    ($producto['margen_absoluto'] / $producto['precio_venta_promedio']) * 100,
                    3
                )
                : 0;

            unset(
                $producto['cantidad_total'],
                $producto['venta_total'],
                $producto['costo_total']
            );
        }

        $resultado = array_values($resultado);

        $pdf = Pdf::loadView('reportes.margen-ganancia', [
            'resultado' => $resultado,
            'fecha_inicio' => $request->fecha_inicio,
            'fecha_fin' => $request->fecha_fin,
        ]);

        return $pdf->stream('reporte-margen-ganancia.pdf');
    }

    public function resumenVentas(Request $request)
    {
        $request->validate([
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'required|date|after_or_equal:fecha_inicio',
        ]);

        $fecha_inicio = Carbon::parse($request->fecha_inicio)->startOfDay();
        $fecha_fin = Carbon::parse($request->fecha_fin)->endOfDay();

        $ventas = Venta::where('estado', 'PROCESADA')
            ->whereBetween('created_at', [$fecha_inicio, $fecha_fin])
            ->get();

        $total_vendido = $ventas->sum('total');

        $numero_ventas = $ventas->count();

        $ticket_promedio = $numero_ventas != 0
            ? $total_vendido / $numero_ventas
            : 0;

        $duracion = $fecha_inicio->diffInDays($fecha_fin) + 1;

        $serie = [];

        if ($duracion <= 31) {

            $serie = $ventas
                ->groupBy(function ($venta) {
                    return Carbon::parse($venta->created_at)->format('Y-m-d');
                })
                ->map(function ($ventas, $fecha) {
                    return [
                        'periodo' => $fecha,
                        'total' => $ventas->sum('total'),
                        'cantidad_ventas' => $ventas->count(),
                    ];
                })
                ->values();
        } else {

            $serie = $ventas
                ->groupBy(function ($venta) {
                    return Carbon::parse($venta->created_at)->startOfWeek()->format('Y-m-d');
                })
                ->map(function ($ventas, $fecha) {
                    return [
                        'periodo' => $fecha,
                        'total' => $ventas->sum('total'),
                        'cantidad_ventas' => $ventas->count(),
                    ];
                })
                ->values();
        }

        $pdf = Pdf::loadView('reportes.resumen-ventas', [

            'fecha_inicio' => $fecha_inicio,
            'fecha_fin' => $fecha_fin,
            'total_vendido' => $total_vendido,
            'numero_ventas' => $numero_ventas,
            'ticket_promedio' => $ticket_promedio,
            'tipo_agrupacion' => $duracion <= 31 ? 'diaria' : 'semanal',
            'serie' => $serie,

        ]);

        return $pdf->stream('reporte-resumen-ventas.pdf');
    }

    public function ventasComparativa(Request $request)
    {
        $request->validate([
            'fecha_inicio_1' => 'required|date',
            'fecha_fin_1' => 'required|date|after_or_equal:fecha_inicio_1',

            'fecha_inicio_2' => 'required|date',
            'fecha_fin_2' => 'required|date|after_or_equal:fecha_inicio_2',
        ]);

        $inicio1 = Carbon::parse($request->fecha_inicio_1)->startOfDay();
        $fin1 = Carbon::parse($request->fecha_fin_1)->endOfDay();

        $inicio2 = Carbon::parse($request->fecha_inicio_2)->startOfDay();
        $fin2 = Carbon::parse($request->fecha_fin_2)->endOfDay();

        $ventas1 = Venta::where('estado', 'PROCESADA')
            ->whereBetween('created_at', [$inicio1, $fin1])
            ->get();

        $ventas2 = Venta::where('estado', 'PROCESADA')
            ->whereBetween('created_at', [$inicio2, $fin2])
            ->get();

        $total1 = $ventas1->sum('total');
        $total2 = $ventas2->sum('total');

        $cantidad1 = $ventas1->count();
        $cantidad2 = $ventas2->count();

        $promedio1 = $cantidad1 > 0 ? $total1 / $cantidad1 : 0;
        $promedio2 = $cantidad2 > 0 ? $total2 / $cantidad2 : 0;

        $variacion = $total1 != 0
            ? (($total2 - $total1) / $total1) * 100
            : 0;

        $dias1 = $inicio1->diffInDays($fin1) + 1;
        $dias2 = $inicio2->diffInDays($fin2) + 1;

        $pdf = Pdf::loadView('reportes.ventas-comparativa', [
            'inicio1' => $inicio1,
            'fin1' => $fin1,
            'inicio2' => $inicio2,
            'fin2' => $fin2,

            'total1' => $total1,
            'total2' => $total2,

            'cantidad1' => $cantidad1,
            'cantidad2' => $cantidad2,

            'promedio1' => $promedio1,
            'promedio2' => $promedio2,

            'variacion' => $variacion,

            'dias1' => $dias1,
            'dias2' => $dias2,
        ]);

        return $pdf->stream('reporte-ventas-comparativa.pdf');
    }

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
}

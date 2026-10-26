<?php

namespace App\Services;

use App\Models\Compra;
use App\Models\Venta;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardService
{
    private const METODOS_PAGO = ['EFECTIVO', 'TARJETA', 'TRANSFERENCIA'];

    
    public function resumen(): array
    {
        $ahora = now();

        $inicioHoy = $ahora->copy()->startOfDay();
        $inicioAyer = $inicioHoy->copy()->subDay();
        $ayerMismaHora = $ahora->copy()->subDay();

        $inicioMes = $ahora->copy()->startOfMonth();
        $inicioMesAnterior = $inicioMes->copy()->subMonthNoOverflow();
        $mesAnteriorMismoDia = $ahora->copy()->subMonthNoOverflow();

        $hoy = $this->ventasEntre($inicioHoy, $ahora);
        $ayer = $this->ventasEntre($inicioAyer, $ayerMismaHora);

        $ticketHoy = $hoy['cantidad'] > 0 ? $hoy['total'] / $hoy['cantidad'] : 0.0;
        $ticketAyer = $ayer['cantidad'] > 0 ? $ayer['total'] / $ayer['cantidad'] : 0.0;

        $gananciaMes = $this->gananciaEntre($inicioMes, $ahora);
        $gananciaMesAnterior = $this->gananciaEntre($inicioMesAnterior, $mesAnteriorMismoDia);

        $alertas = $this->alertas();

        return [
            'generado_en' => $ahora->toIso8601String(),
            'kpis' => [
                'ventas_hoy' => $this->kpi($hoy['total'], $ayer['total']),
                'transacciones_hoy' => $this->kpi($hoy['cantidad'], $ayer['cantidad']),
                'ticket_promedio' => $this->kpi($ticketHoy, $ticketAyer),
                'ganancia_mes' => $this->kpi($gananciaMes, $gananciaMesAnterior),
                'alertas' => [
                    'total' => $alertas['total'],
                    'altas' => $alertas['altas'],
                ],
            ],
            'ultimas_ventas' => $this->ultimasVentas(),
            'metodos_pago' => $this->metodosPago($inicioMes, $ahora),
            'alertas' => $alertas['lista'],
            'top_productos' => $this->topProductos($inicioMes, $ahora),
            'cuentas_por_pagar' => $this->cuentasPorPagar($ahora),
        ];
    }

    public function ventasCompras(int $anio): array
    {
        $inicio = Carbon::create($anio, 1, 1)->startOfDay();
        $fin = $inicio->copy()->endOfYear();

        $ventas = Venta::where('estado', 'PROCESADA')
            ->whereBetween('created_at', [$inicio, $fin])
            ->selectRaw('CAST(EXTRACT(MONTH FROM created_at) AS INTEGER) AS mes, SUM(total) AS total')
            ->groupBy('mes')
            ->pluck('total', 'mes');

        $compras = Compra::where('es_anulado', false)
            ->whereBetween('fecha_emision', [$inicio->toDateString(), $fin->toDateString()])
            ->selectRaw('CAST(EXTRACT(MONTH FROM fecha_emision) AS INTEGER) AS mes, SUM(monto_total) AS total')
            ->groupBy('mes')
            ->pluck('total', 'mes');

        $serie = fn ($datos) => collect(range(1, 12))
            ->map(fn ($mes) => round((float) ($datos[$mes] ?? 0), 2))
            ->all();

        $primerRegistro = Venta::min('created_at');
        $primerAnio = $primerRegistro ? Carbon::parse($primerRegistro)->year : $anio;

        return [
            'anio' => $anio,
            'anios_disponibles' => range(now()->year, min($primerAnio, $anio)),
            'ventas' => $serie($ventas),
            'compras' => $serie($compras),
        ];
    }

    private function kpi(float|int $valor, float|int $anterior): array
    {
        return [
            'valor' => round((float) $valor, 2),
            'anterior' => round((float) $anterior, 2),
            'variacion' => $this->variacion((float) $valor, (float) $anterior),
        ];
    }

    private function variacion(float $actual, float $anterior): ?float
    {
        if ($anterior == 0.0) {
            return null;
        }

        return round((($actual - $anterior) / abs($anterior)) * 100, 1);
    }

    private function ventasEntre(Carbon $desde, Carbon $hasta): array
    {
        $r = Venta::where('estado', 'PROCESADA')
            ->whereBetween('created_at', [$desde, $hasta])
            ->selectRaw('COALESCE(SUM(total), 0) AS total, COUNT(*) AS cantidad')
            ->first();

        return [
            'total' => (float) $r->total,
            'cantidad' => (int) $r->cantidad,
        ];
    }

    private function gananciaEntre(Carbon $desde, Carbon $hasta): float
    {
        $ventasNetas = (float) Venta::where('estado', 'PROCESADA')
            ->whereBetween('created_at', [$desde, $hasta])
            ->selectRaw('COALESCE(SUM(gravado + exento), 0) AS neto')
            ->value('neto');

        $costo = (float) DB::table('lote_detalles_venta AS ldv')
            ->join('detalles_venta AS dv', 'dv.id', '=', 'ldv.detalle_venta_id')
            ->join('ventas AS v', 'v.id', '=', 'dv.venta_id')
            ->join('lotes AS l', 'l.id', '=', 'ldv.lote_id')
            ->where('v.estado', 'PROCESADA')
            ->whereBetween('v.created_at', [$desde, $hasta])
            ->selectRaw('COALESCE(SUM(ldv.cantidad_tomada * l.costo_unitario_compra), 0) AS costo')
            ->value('costo');

        return round($ventasNetas - $costo, 2);
    }

    private function ultimasVentas(): array
    {
        return Venta::with('vendidoPor:id,name')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->limit(8)
            ->get(['id', 'numero_factura', 'tipo_pago', 'estado', 'total', 'vendido_por', 'created_at'])
            ->map(fn (Venta $v) => [
                'id' => $v->id,
                'numero_factura' => $v->numero_factura,
                'tipo_pago' => $v->tipo_pago,
                'estado' => $v->estado,
                'total' => (float) $v->total,
                'cajero' => $v->vendidoPor?->name,
                'fecha' => $v->created_at->toIso8601String(),
            ])
            ->all();
    }

    private function metodosPago(Carbon $desde, Carbon $hasta): array
    {
        $filas = Venta::where('estado', 'PROCESADA')
            ->whereBetween('created_at', [$desde, $hasta])
            ->selectRaw('tipo_pago, COALESCE(SUM(total), 0) AS total, COUNT(*) AS cantidad')
            ->groupBy('tipo_pago')
            ->get()
            ->keyBy('tipo_pago');

        $totalGeneral = (float) $filas->sum(fn ($f) => (float) $f->total);

        return collect(self::METODOS_PAGO)
            ->map(function (string $metodo) use ($filas, $totalGeneral) {
                $fila = $filas->get($metodo);
                $total = $fila ? (float) $fila->total : 0.0;

                return [
                    'tipo' => $metodo,
                    'total' => round($total, 2),
                    'cantidad' => $fila ? (int) $fila->cantidad : 0,
                    'porcentaje' => $totalGeneral > 0 ? round(($total / $totalGeneral) * 100, 1) : 0.0,
                ];
            })
            ->all();
    }

    private function alertas(): array
    {
        $base = DB::table('alertas')->where('estado', 'ACTIVA');

        $conteo = (clone $base)
            ->selectRaw("COUNT(*) AS total, COUNT(*) FILTER (WHERE prioridad = 'ALTA') AS altas")
            ->first();

        $lista = (clone $base)
            ->orderByRaw("CASE prioridad WHEN 'ALTA' THEN 1 WHEN 'MEDIA' THEN 2 ELSE 3 END")
            ->orderByDesc('created_at')
            ->limit(5)
            ->get(['id', 'mensaje', 'tipo', 'prioridad', 'created_at'])
            ->map(fn ($a) => [
                'id' => $a->id,
                'mensaje' => $a->mensaje,
                'tipo' => $a->tipo,
                'prioridad' => $a->prioridad,
                'fecha' => Carbon::parse($a->created_at)->toIso8601String(),
            ])
            ->all();

        return [
            'total' => (int) $conteo->total,
            'altas' => (int) $conteo->altas,
            'lista' => $lista,
        ];
    }

    private function topProductos(Carbon $desde, Carbon $hasta): array
    {
        return DB::table('detalles_venta AS dv')
            ->join('ventas AS v', 'v.id', '=', 'dv.venta_id')
            ->where('v.estado', 'PROCESADA')
            ->whereBetween('v.created_at', [$desde, $hasta])
            ->groupBy('dv.nombre_producto')
            ->selectRaw('dv.nombre_producto AS producto, SUM(dv.subtotal) AS monto, COUNT(DISTINCT dv.venta_id) AS ventas')
            ->orderByDesc('monto')
            ->limit(5)
            ->get()
            ->map(fn ($p) => [
                'producto' => $p->producto,
                'monto' => round((float) $p->monto, 2),
                'ventas' => (int) $p->ventas,
            ])
            ->all();
    }

    private function cuentasPorPagar(Carbon $ahora): array
    {
        $base = Compra::where('es_anulado', false)
            ->whereIn('estado_pago', ['PENDIENTE', 'ABONADO', 'VENCIDO']);

        $resumen = (clone $base)
            ->selectRaw(
                "COALESCE(SUM(COALESCE(monto_total, 0) - COALESCE(abono, 0)), 0) AS saldo,
                 COUNT(*) AS cantidad,
                 COUNT(*) FILTER (WHERE estado_pago = 'VENCIDO' OR fecha_vencimiento_pago < ?) AS vencidas",
                [$ahora->toDateString()]
            )
            ->first();

        $proximas = (clone $base)
            ->with('proveedor:id,nombre')
            ->orderByRaw('fecha_vencimiento_pago ASC NULLS LAST')
            ->limit(5)
            ->get(['id', 'numero_documento', 'monto_total', 'abono', 'estado_pago', 'fecha_vencimiento_pago', 'proveedor_id'])
            ->map(fn (Compra $c) => [
                'id' => $c->id,
                'numero_documento' => $c->numero_documento,
                'proveedor' => $c->proveedor?->nombre,
                'saldo' => round((float) $c->monto_total - (float) $c->abono, 2),
                'estado_pago' => $c->estado_pago,
                'fecha_vencimiento' => $c->fecha_vencimiento_pago?->toDateString(),
            ])
            ->all();

        return [
            'saldo_total' => round((float) $resumen->saldo, 2),
            'cantidad' => (int) $resumen->cantidad,
            'vencidas' => (int) $resumen->vencidas,
            'proximas' => $proximas,
        ];
    }
}
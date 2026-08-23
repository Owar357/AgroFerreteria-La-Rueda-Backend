<?php

namespace App\Console\Commands;

use App\Models\Alerta;
use App\Models\Compra;
use App\Models\Lote;
use App\Models\Presentacion;
use Illuminate\Console\Command;

class GenerarAlertas extends Command
{
    protected $signature = 'alerta:generar';

    protected $description = 'Genera y actualiza alertas de stock, lotes y compras';

    public function handle()
    {
        $this->info('Iniciando generación de alertas...');
        $this->procesarCompras();
        $this->procesarLotes();
        $this->procesarStock();
        $this->info('Proceso terminado.');
    }

    // ============================================================
    // COMPRAS
    // ============================================================
    public function procesarCompras()
    {
        $compras = Compra::whereIn('estado_pago', ['PENDIENTE', 'ABONADO'])->get();
        $this->info("Compras encontradas: {$compras->count()}");

        foreach ($compras as $compra) {
            $this->procesarCompra($compra);
        }
    }

    public function procesarCompra(Compra $compra)
    {
        if ($compra->estado_pago === 'PAGADO') {
            Alerta::whereIn('tipo', ['COMPRA PENDIENTE DE PAGO', 'COMPRA VENCIDA'])
                ->where('compra_id', $compra->id)
                ->where('estado', 'ACTIVA')
                ->update(['estado' => 'RESUELTA']);

            return;
        }

        if (is_null($compra->fecha_vencimiento_pago)) {
            return;
        }

        $hoy = now()->startOfDay();
        $vencimiento = $compra->fecha_vencimiento_pago;

        if ($vencimiento->lt($hoy)) {
            Alerta::where('compra_id', $compra->id)
                ->where('tipo', 'COMPRA PENDIENTE DE PAGO')
                ->where('estado', 'ACTIVA')
                ->update(['estado' => 'RESUELTA']);

            if ($compra->estado_pago !== 'VENCIDO') {
                $compra->update(['estado_pago' => 'VENCIDO']);
            }

            $alerta = Alerta::firstOrCreate([
                'tipo' => 'COMPRA VENCIDA',
                'compra_id' => $compra->id,
                'estado' => 'ACTIVA',
            ], [
                'prioridad' => 'ALTA',
                'mensaje' => "La compra #{$compra->numero_documento} venció sin pagarse (vencía el {$vencimiento->toDateString()})",
            ]);

            $this->reNotificarSiToca($alerta);

            return;
        }

        $diasParaVencer = $hoy->diffInDays($vencimiento, false);

        if ($diasParaVencer <= 7) {
            $alerta = Alerta::firstOrCreate([
                'tipo' => 'COMPRA PENDIENTE DE PAGO',
                'compra_id' => $compra->id,
                'estado' => 'ACTIVA',
            ], [
                'prioridad' => $diasParaVencer <= 2 ? 'ALTA' : 'MEDIA',
                'mensaje' => "La compra #{$compra->numero_documento} vence en {$diasParaVencer} días ({$vencimiento->toDateString()})",
            ]);

            $this->reNotificarSiToca($alerta, 24 * 5);
        }
    }

    // ============================================================
    // LOTES
    // ============================================================
    public function procesarLotes()
    {
        $lotes = Lote::where('estado', 'ACTIVO')
            ->where('cantidad_actual', '>', 0)
            ->get();

        $this->info("Lotes encontrados: {$lotes->count()}");

        foreach ($lotes as $lote) {
            $this->procesarLote($lote);
        }
    }

    public function procesarLote(Lote $lote)
    {
        if (is_null($lote->fecha_vencimiento)) {
            return;
        }

        $hoy = now()->startOfDay();
        $vencimiento = $lote->fecha_vencimiento->copy()->startOfDay();
        $unMesAntes = $vencimiento->copy()->subMonthNoOverflow();

        $diasParaVencer = $hoy->diffInDays($vencimiento, false);
        $diasVencido = $vencimiento->diffInDays($hoy, false);

        if ($hoy->gte($unMesAntes) && $hoy->lt($vencimiento)) {
            $alerta = Alerta::firstOrCreate([
                'tipo' => 'LOTE POR VENCER',
                'lote_id' => $lote->id,
                'estado' => 'ACTIVA',
            ], [
                'prioridad' => 'MEDIA',
                'mensaje' => "El lote {$lote->lote_interno} vence en {$diasParaVencer} días (vence el {$lote->fecha_vencimiento->toDateString()})",
            ]);

            $this->reNotificarSiToca($alerta, 24 * 5);
        }

        if ($hoy->gte($vencimiento)) {
            Alerta::where('lote_id', $lote->id)
                ->where('tipo', 'LOTE POR VENCER')
                ->where('estado', 'ACTIVA')
                ->update(['estado' => 'RESUELTA']);

            if ($lote->estado !== 'VENCIDO') {
                $lote->update(['estado' => 'VENCIDO']);
            }

            $alerta = Alerta::firstOrCreate([
                'tipo' => 'LOTE VENCIDO',
                'lote_id' => $lote->id,
                'estado' => 'ACTIVA',
            ], [
                'prioridad' => 'ALTA',
                'mensaje' => "El lote {$lote->lote_interno} venció el ({$lote->fecha_vencimiento->toDateString()})",
            ]);

            if ($diasVencido <= 7) {
                $this->reNotificarSiToca($alerta);
            }
        }
    }

    // ============================================================
    // STOCK (ACTUALIZADO)
    // ============================================================
    public function procesarStock()
    {
        $presentaciones = Presentacion::where('stock_minimo', '>', 0)
            ->with(['producto.unidadMedida', 'lotes' => function ($query) {
                $query->where('estado', 'ACTIVO');
            }])
            ->get();

        $this->info("Presentaciones con stock mínimo configurado: {$presentaciones->count()}");

        foreach ($presentaciones as $presentacion) {
            $this->procesarStockPresentacion($presentacion);
        }
    }

    public function procesarStockPresentacion(Presentacion $presentacion)
    {

        $stockActual = $presentacion->lotes->sum('cantidad_actual') ?? 0;

        $unidadNombre = $presentacion->producto->unidadMedida->nombre ?? 'unidades';

        if ($stockActual > 0 && $stockActual <= $presentacion->stock_minimo) {
            // Cerrar alerta de "AGOTADO" si existe
            Alerta::where('presentacion_id', $presentacion->id)
                ->where('tipo', 'STOCK AGOTADO')
                ->where('estado', 'ACTIVA')
                ->update(['estado' => 'RESUELTA']);

            $alerta = Alerta::firstOrCreate([
                'tipo' => 'STOCK MINIMO',
                'presentacion_id' => $presentacion->id,
                'estado' => 'ACTIVA',
            ], [
                'prioridad' => 'MEDIA',
                'mensaje' => "Stock bajo: {$presentacion->producto->nombre} ({$presentacion->nombre}) - quedan {$stockActual} {$unidadNombre}.",
            ]);

            $this->reNotificarSiToca($alerta, 24 * 2, false);
        }

        if ($stockActual == 0) {

            Alerta::where('presentacion_id', $presentacion->id)
                ->where('tipo', 'STOCK MINIMO')
                ->where('estado', 'ACTIVA')
                ->update(['estado' => 'RESUELTA']);

            $alerta = Alerta::firstOrCreate([
                'tipo' => 'STOCK AGOTADO',
                'presentacion_id' => $presentacion->id,
                'estado' => 'ACTIVA',
            ], [
                'prioridad' => 'ALTA',
                'mensaje' => "Stock agotado: {$presentacion->producto->nombre} ({$presentacion->nombre}) - sin unidades disponibles.",
            ]);

            $this->reNotificarSiToca($alerta, 12, false);
        }

        if ($stockActual > $presentacion->stock_minimo) {
            Alerta::where('presentacion_id', $presentacion->id)
                ->whereIn('tipo', ['STOCK MINIMO', 'STOCK AGOTADO'])
                ->where('estado', 'ACTIVA')
                ->update(['estado' => 'RESUELTA']);
        }
    }

    // ============================================================
    // HELPER
    // ============================================================
    private function reNotificarSiToca(Alerta $alerta, int $horasIntervalo = 24, bool $respetarLeidaPor = true)
    {
        if ($respetarLeidaPor && ! is_null($alerta->leida_por)) {
            return;
        }

        $intervalo = now()->subHours($horasIntervalo);

        if (is_null($alerta->ultima_notificacion_at) || $alerta->ultima_notificacion_at < $intervalo) {
            $alerta->update([
                'leida' => false,
                'leida_por' => null,
                'ultima_notificacion_at' => now(),
            ]);
        }
    }
}

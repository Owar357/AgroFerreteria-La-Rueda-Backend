<?php

namespace App\Console\Commands;

use App\Models\Alerta;
use App\Models\Compra;
use Illuminate\Console\Command;

class GenerarAlertas extends Command
{
    protected $signature = 'alerta:generar';

    protected $description = 'Genera y actualiza alertas de stock, lotes y compras';

    public function handle()
    {
        $this->info('Iniciando generación de alertas...');
        $this->procesarCompras();
        $this->info('Proceso terminado.');
    }

    // Recorre TODAS las compras y llama a procesarCompra() una por una.
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
        // Ya pagada -> cerramos cualquier alerta activa
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

        // Caso 1: ya venció y sigue sin pagarse -> alerta final
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

        // Caso 2: se acerca la fecha límite -> recordatorio
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

            $this->reNotificarSiToca($alerta);
        }
    }

    private function reNotificarSiToca(Alerta $alerta)
    {
        $intervalo = now()->subHours(24);

        if (is_null($alerta->ultima_notificacion_at) || $alerta->ultima_notificacion_at < $intervalo) {
            $alerta->update([
                'leida' => false,
                'ultima_notificacion_at' => now(),
            ]);
        }
    }
}

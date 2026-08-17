<?php

namespace App\Console\Commands;

use App\Models\Alerta;
use App\Models\Compra;
use App\Models\Lote;
use App\Models\Producto;
use Illuminate\Console\Command;

use function Illuminate\Support\now;

class GenerarAlertas extends Command
{
    protected $signature = 'alerta:generar';

    protected $description = 'Genera y actualiza alertas de stock, lotes y compras';

    // Punto de entrada del comando: orquesta los 3 módulos de alertas en orden.
    public function handle()
    {
        $this->info('Iniciando generación de alertas...');
        $this->procesarCompras();
        $this->procesarLotes();
        $this->procesarStock();
        $this->info('Proceso terminado.');
    }

    // Trae las compras pendientes/abonadas y las procesa una por una.
    public function procesarCompras()
    {
        $compras = Compra::whereIn('estado_pago', ['PENDIENTE', 'ABONADO'])->get();

        $this->info("Compras encontradas: {$compras->count()}");

        foreach ($compras as $compra) {
            $this->procesarCompra($compra);
        }
    }

    // Trae los lotes activos con existencias y los procesa uno por uno.
    public function procesarLotes()
    {
        $lotes = Lote::whereIn('estado', ['ACTIVO'])
            ->where('cantidad_actual', '>', 0)
            ->get();

        $this->info("Lotes encontrados: {$lotes->count()}");

        foreach ($lotes as $lote) {
            $this->procesarLote($lote);
        }
    }

    // Trae los productos con stock mínimo configurado y los procesa uno por uno.
    private function procesarStock()
    {
        $productos = Producto::whereNotNull('stock_minimo')->get();

        $this->info("Productos con stock mínimo configurado: {$productos->count()}");

        foreach ($productos as $producto) {
            $this->procesarStockProducto($producto);
        }
    }

    // Evalúa UNA compra: genera/cierra alertas de "pendiente de pago" o "vencida" según su fecha y estado.
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

            $this->reNotificarSiToca($alerta,24 * 5);
        }
    }

    // Evalúa UN lote: genera/cierra alertas de "por vencer" o "vencido" según su fecha de vencimiento.
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

        // Alerta sobre que un lote vencerá en el plazo de un mes y aún no vence
        if ($hoy->gte($unMesAntes) && $hoy->lt($vencimiento)) {

            $alerta = Alerta::firstOrCreate([
                'tipo' => 'LOTE POR VENCER',
                'lote_id' => $lote->id,
                'estado' => 'ACTIVA',
            ], [
                'prioridad' => 'MEDIA',
                'mensaje' => "El lote {$lote->lote_interno} vence en {$diasParaVencer} días, vence el ({$lote->fecha_vencimiento->toDateString()})",
            ]);

            $this->reNotificarSiToca($alerta, 24 * 5);
        }

        // Alerta para notificar que un lote ya venció
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

    // Evalúa UN producto: suma el stock de todos sus lotes (a través de sus presentaciones)
    // y genera/cierra alertas de "stock mínimo" o "agotado".
    private function procesarStockProducto(Producto $producto)
    {
        $stockActual = Lote::whereHas('presentacion', function ($query) use ($producto) {
            $query->where('producto_id', $producto->id);
        })
            ->where('estado', 'ACTIVO')
            ->sum('cantidad_actual');

        // Caso 1: queda algo, pero está en o por debajo del mínimo
        if ($stockActual > 0 && $stockActual <= $producto->stock_minimo) {

            Alerta::where('producto_id', $producto->id)
                ->where('tipo', 'STOCK AGOTADO')
                ->where('estado', 'ACTIVA')
                ->update(['estado' => 'RESUELTA']);

            $alerta = Alerta::firstOrCreate([
                'tipo' => 'STOCK MINIMO',
                'producto_id' => $producto->id,
                'estado' => 'ACTIVA',
            ], [
                'prioridad' => 'MEDIA',
                'mensaje' => "El producto {$producto->nombre} está en stock bajo: quedan {$stockActual} {$producto->unidad_base}",
            ]);

            $this->reNotificarSiToca($alerta, 24 * 2, false);
        }

        // Caso 2: no queda nada -> alerta final, sin límite de días
        if ($stockActual == 0) {

            Alerta::where('producto_id', $producto->id)
                ->where('tipo', 'STOCK MINIMO')
                ->where('estado', 'ACTIVA')
                ->update(['estado' => 'RESUELTA']);

            $alerta = Alerta::firstOrCreate([
                'tipo' => 'STOCK AGOTADO',
                'producto_id' => $producto->id,
                'estado' => 'ACTIVA',
            ], [
                'prioridad' => 'ALTA',
                'mensaje' => "El producto {$producto->nombre} ha agotado todas sus existencias",
            ]);

            $this->reNotificarSiToca($alerta, 12, false);
        }

        // Caso 3: el stock se recuperó por encima del mínimo -> cerramos lo que estuviera activo
        if ($stockActual > $producto->stock_minimo) {
            Alerta::where('producto_id', $producto->id)
                ->whereIn('tipo', ['STOCK MINIMO', 'STOCK AGOTADO'])
                ->where('estado', 'ACTIVA')
                ->update(['estado' => 'RESUELTA']);
        }
    }

    // Helper compartido: decide si "revive" una alerta (leida=false) según cuánto pasó desde el último aviso.
    private function reNotificarSiToca(Alerta $alerta, int $horasIntervalo = 24, bool $respetarLeidaPor = true)
    {
        // Si el usuario ya la marcó como leída y este tipo de alerta respeta esa decisión, no la tocamos.
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

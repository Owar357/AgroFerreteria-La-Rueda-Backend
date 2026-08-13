<?php

namespace App\Console\Commands;

use App\Models\Alerta;
use App\Models\Compra;
use Illuminate\Console\Command;

class GenerarAlertas extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'alertar:generar';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Genera y actualiza de stock, lotes y compras';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->procesarCompra();
    }

    //Este recorre TODAS las compras  y llama a procesarCompra() una por una.
    public function procesarCompras(){
         $compra = Compra::whereIn('estado_pago', ['PENDIENTE', 'ABONADO'])->get();
        
         foreach($compra as $compra){
            $this->procesarCompra($compra);
         }
    }

    public function procesarCompra(Compra $compra){
        if($compra->estado_pago === 'PAGADO'){
            Alerta::whereIn('tipo', ['COMPRA PENDIENTE DE PAGO','COMPRA VENCIDA'])
            ->where('compra_id', $compra->id)
            ->where('estado' , 'ACTIVA')
            ->update(['estado' => 'RESUELTA']);
          return;
        }

    if(is_null($compra->fecha_vencimiento_pago)){
        return;
    } 

    $hoy = now()->startOfDay();
    $vencimiento = $compra->fecha_vencimiento_pago;

    if($vencimiento->lt($hoy)){
        Alerta::where('compra_id', $compra->id)
        ->where('tipo','COMPRA PENDIENTE DE PAGO')
        ->where('estado','ACTIVA')
        ->update(['estado' => 'RESUELTA']);
    }

    if($compra->estado_pago != 'VENCIDO'){
         $compra->update(['estado_pago' => 'VENCIDO']);    
    }

    $alerta = Alerta::firstOrCreate([
        'tipo' => 'COMPRA VENCIDA',
        'compra_id' => $compra->id,
        'estado' => 'ACTIVA'
    ],
       ['prioridad' => 'ALTA', "mensaje' => 'La compra #{$compra->numero_documento} vencio sin pagarse (vencia en {$vencimiento->toDateString()})", ]);

       $this->reNotificarSiToca($alerta);
       return;
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

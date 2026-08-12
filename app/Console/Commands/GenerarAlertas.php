<?php

namespace App\Console\Commands;

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
        //
    }


    public function procesarCompra(){
         $compra = Compra::whereIn('estado_pago', ['PENDIENTE', 'ABONADO'])->get();
        
    }
}

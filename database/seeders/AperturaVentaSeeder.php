<?php

namespace Database\Seeders;

use App\Models\AperturaCaja;
use App\Models\AperturaVenta;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AperturaVentaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
    
        $cajero = User::where('email', 'admin@test.com')->firstOrFail();

        $aperturaCaja = AperturaCaja::where('estado', 'ABIERTO')->firstOrFail();

        AperturaVenta::firstOrCreate(
            [
                'cajero_id' => $cajero->id,
                'estado' => 'ABIERTA',
            ],
            [
                'fecha_hora_apertura' => now(),
                'fecha_hora_cierre' => null,
                'monto_inicial' => 50.00,
                'monto_esperado' => null,
                'monto_contado' => null,
                'diferencia' => null,
                'estado_arqueo' => null,
                'apertura_caja_id' => $aperturaCaja->id,
                'cerrada_por' => null,
            ]
        );
    
    }
}

<?php

namespace Database\Seeders;

use App\Models\TurnoCaja;
use App\Models\User;
use Illuminate\Database\Seeder;

class TurnoCajaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
         $usuario = User::where('email', 'admin@test.com')->firstOrFail();

        TurnoCaja::firstOrCreate(
            [
                'abierta_por' => $usuario->id,
                'estado' => 'ABIERTO',
            ],
            [
                'fecha_hora_apertura' => now(),
                'fecha_hora_cierre' => null,
                'monto_inicial' => 50.00,
                'monto_esperado' => null,
                'monto_real_caja' => null,
                'diferencia' => null,
                'cerrada_por' => null,
            ]
        );
    }
}

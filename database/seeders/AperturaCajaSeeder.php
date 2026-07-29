<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AperturaCajaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $admin = User::where('email','admin@test.com')->firstOrFail();

         AperturaCaja::firstOrCreate(
            ['estado' => 'ABIERTO'],
            [
                'fecha_hora_apertura' => now(),
                'fecha_hora_cierre' => null,
                'sucursal_id' => null,
                'abierta_por' => $admin->id,
                'cerrada_por' => null,
            ]
        );
    }
}

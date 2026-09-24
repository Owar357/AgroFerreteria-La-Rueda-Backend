<?php

namespace Database\Seeders;

use App\Models\Configuracion;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ConfiguracionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
         // firstOrCreate: si el admin ya editó el valor, no lo sobrescribe
        Configuracion::firstOrCreate(
            ['clave' => Configuracion::FONDO_FIJO_CAJA],
            [
                'valor' => Configuracion::FONDO_FIJO_DEFECTO,
                'descripcion' => 'Fondo fijo de operación que debe quedar en la gaveta al iniciar cada turno (USD).',
            ]
        );
    }
}

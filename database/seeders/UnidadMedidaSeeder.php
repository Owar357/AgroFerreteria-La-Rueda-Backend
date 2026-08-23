<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UnidadMedidaSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('unidad_medidas')->insert([
            // Masa
            ['nombre' => 'Gramo', 'abreviatura' => 'g', 'magnitud' => 'Masa', 'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'Libra', 'abreviatura' => 'lb', 'magnitud' => 'Masa', 'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'Kilogramo', 'abreviatura' => 'kg', 'magnitud' => 'Masa', 'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'Arroba', 'abreviatura' => '@', 'magnitud' => 'Masa', 'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'Quintal', 'abreviatura' => 'qq', 'magnitud' => 'Masa', 'created_at' => now(), 'updated_at' => now()],

            // Volumen
            ['nombre' => 'Mililitro', 'abreviatura' => 'ml', 'magnitud' => 'Volumen', 'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'Litro', 'abreviatura' => 'L', 'magnitud' => 'Volumen', 'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'Galón', 'abreviatura' => 'gal', 'magnitud' => 'Volumen', 'created_at' => now(), 'updated_at' => now()],

            // Longitud
            ['nombre' => 'Metro', 'abreviatura' => 'm', 'magnitud' => 'Longitud', 'created_at' => now(), 'updated_at' => now()],

            // Unidad (conteo)
            ['nombre' => 'Unidad', 'abreviatura' => 'und', 'magnitud' => 'Unidad', 'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'Pieza', 'abreviatura' => 'pz', 'magnitud' => 'Unidad', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }
}   
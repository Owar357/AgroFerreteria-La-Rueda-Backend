<?php

namespace Database\Seeders;

use App\Models\Cliente;
use Illuminate\Database\Seeder;

class ClienteSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Cliente::firstOrCreate(
            ['id' => 1],
            [
                'tipo_persona' => 'NATURAL',
                'nombre' => 'CONSUMIDOR FINAL',
                'tipo_documento_receptor' => 13,
                'numero_documento' => 06411613-0,
                'nrc' => null,
                'cod_actividad' => null,
                'giro_actividad' => null,
                'cod_departamento' => null,
                'cod_municipio' => null,
                'complemento' => null,
                'correo' => 'bart89761@gmail.com',
                'activo' => true,
                'registrado_por' => 1,
            ]
        );
    }
}

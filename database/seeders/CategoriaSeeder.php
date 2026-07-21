<?php

namespace Database\Seeders;

use App\Models\Categoria;
use App\Models\User;
use Illuminate\Database\Seeder;

class CategoriaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
       $usuario = User::where('email', 'admin@test.com')->firstOrFail();

        $categorias = [
            'Fertilizantes',
            'Insecticidas y Fungicidas',
            'Semillas',
            'Herramientas Manuales',
            'Equipo de Riego',
            'Ferretería General',
            'Repuestos Agrícolas',
            'Abonos Orgánicos',
        ];

        foreach ($categorias as $nombre) {
            Categoria::firstOrCreate(
                ['nombre' => $nombre],
                [
                    'activo' => true,
                    'creado_por' => $usuario->id,
                ]
            );
        }
    }
}

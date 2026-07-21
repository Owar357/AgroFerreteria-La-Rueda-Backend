<?php

namespace Database\Seeders;

use App\Models\Proveedor;
use Illuminate\Database\Seeder;

class ProveedorSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
          $proveedores = [
            [
                'nombre' => 'Fertica El Salvador',
                'direccion' => 'Km 12 Carretera Panamericana, San Salvador',
                'correo' => 'ventas@fertica.com.sv',
                'telefono' => '22334400',
                'tipo_persona' => 'JURIDICA',
                'activo' => true,
            ],
            [
                'nombre' => 'Distribuidora Agrícola Corteva',
                'direccion' => 'Zona Industrial Santa Elena, Antiguo Cuscatlán',
                'correo' => 'contacto@corteva.com.sv',
                'telefono' => '22456789',
                'tipo_persona' => 'JURIDICA',
                'activo' => true,
            ],
            [
                'nombre' => 'Ferretería y Suministros Truper SV',
                'direccion' => 'Bulevar del Ejército, San Salvador',
                'correo' => 'ventas@truper.com.sv',
                'telefono' => '22778899',
                'tipo_persona' => 'JURIDICA',
                'activo' => true,
            ],
            [
                'nombre' => 'Juan Ramírez - Insumos Agrícolas',
                'direccion' => 'Cantón El Rosario, Sonsonate',
                'correo' => null,
                'telefono' => '78901234',
                'tipo_persona' => 'NATURAL',
                'activo' => true,
            ],
        ];

        foreach ($proveedores as $proveedor) {
            Proveedor::firstOrCreate(
                ['nombre' => $proveedor['nombre']],
                [
                    'direccion' => $proveedor['direccion'],
                    'correo' => $proveedor['correo'],
                    'telefono' => $proveedor['telefono'],
                    'tipo_persona' => $proveedor['tipo_persona'],
                    'activo' => $proveedor['activo'],
                ]
            );
        }
    }
}

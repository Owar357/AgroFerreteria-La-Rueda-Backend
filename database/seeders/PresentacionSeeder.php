<?php

namespace Database\Seeders;

use App\Models\Presentacion;
use App\Models\Producto;
use Illuminate\Database\Seeder;

class PresentacionSeeder extends Seeder
{
    public function run(): void
    {
        $presentacionesPorProducto = [
            'FER-TRI15-01' => [
                ['nombre' => 'Libra', 'factor_conversion' => 1, 'precio_venta' => 0.60],
                ['nombre' => 'Quintal', 'factor_conversion' => 100, 'precio_venta' => 55.00],
            ],
            'FER-URE-01' => [
                ['nombre' => 'Libra', 'factor_conversion' => 1, 'precio_venta' => 0.55],
                ['nombre' => 'Quintal', 'factor_conversion' => 100, 'precio_venta' => 50.00],
            ],
            'INS-LOR-01' => [
                ['nombre' => 'Litro', 'factor_conversion' => 1, 'precio_venta' => 8.50],
                ['nombre' => 'Galón', 'factor_conversion' => 3.785, 'precio_venta' => 28.00],
            ],
            'FUN-CUP-01' => [
                ['nombre' => 'Kilogramo', 'factor_conversion' => 1, 'precio_venta' => 6.00],
                ['nombre' => 'Bolsa 500g', 'factor_conversion' => 0.5, 'precio_venta' => 3.25],
            ],
            'SEM-MAI59-01' => [
                ['nombre' => 'Libra', 'factor_conversion' => 1, 'precio_venta' => 1.10],
                ['nombre' => 'Saco 50lb', 'factor_conversion' => 50, 'precio_venta' => 50.00],
            ],
            'SEM-FRJ-01' => [
                ['nombre' => 'Libra', 'factor_conversion' => 1, 'precio_venta' => 0.85],
                ['nombre' => 'Quintal', 'factor_conversion' => 100, 'precio_venta' => 78.00],
            ],
            'HER-MC22-01' => [
                ['nombre' => 'Unidad', 'factor_conversion' => 1, 'precio_venta' => 9.60],
            ],
            'HER-PLA-01' => [
                ['nombre' => 'Unidad', 'factor_conversion' => 1, 'precio_venta' => 12.50],
            ],
            'RIE-MNG-01' => [
                ['nombre' => 'Metro', 'factor_conversion' => 1, 'precio_venta' => 0.75],
                ['nombre' => 'Rollo 100m', 'factor_conversion' => 100, 'precio_venta' => 65.00],
            ],
            'RIE-ASP-01' => [
                ['nombre' => 'Unidad', 'factor_conversion' => 1, 'precio_venta' => 4.50],
            ],
            'FRR-ALA-01' => [
                ['nombre' => 'Libra', 'factor_conversion' => 1, 'precio_venta' => 0.90],
                ['nombre' => 'Rollo 5lb', 'factor_conversion' => 5, 'precio_venta' => 4.25],
            ],
            'FRR-CLV-01' => [
                ['nombre' => 'Libra', 'factor_conversion' => 1, 'precio_venta' => 0.65],
            ],
            'REP-DIS-01' => [
                ['nombre' => 'Unidad', 'factor_conversion' => 1, 'precio_venta' => 35.00],
            ],
            'REP-CUC-01' => [
                ['nombre' => 'Unidad', 'factor_conversion' => 1, 'precio_venta' => 6.75],
            ],
            'ABO-BOC-01' => [
                ['nombre' => 'Libra', 'factor_conversion' => 1, 'precio_venta' => 0.35],
                ['nombre' => 'Quintal', 'factor_conversion' => 100, 'precio_venta' => 30.00],
            ],
            'ABO-HUM-01' => [
                ['nombre' => 'Libra', 'factor_conversion' => 1, 'precio_venta' => 0.40],
                ['nombre' => 'Quintal', 'factor_conversion' => 100, 'precio_venta' => 35.00],
            ],
        ];

        foreach ($presentacionesPorProducto as $codigoProducto => $presentaciones) {
            $producto = Producto::where('codigo', $codigoProducto)->firstOrFail();

            foreach ($presentaciones as $presentacion) {
                Presentacion::firstOrCreate(
                    [
                        'producto_id' => $producto->id,
                        'nombre' => $presentacion['nombre'],
                    ],
                    [
                        'factor_conversion' => $presentacion['factor_conversion'],
                        'precio_venta' => $presentacion['precio_venta'],
                        'activo' => true,
                    ]
                );
            }
        }
    }
}

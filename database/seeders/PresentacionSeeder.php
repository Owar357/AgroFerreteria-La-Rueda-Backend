<?php

namespace Database\Seeders;

use App\Models\Presentacion;
use App\Models\Producto;
use App\Models\UnidadMedida;
use Illuminate\Database\Seeder;

class PresentacionSeeder extends Seeder
{
    public function run(): void
    {
        $unidades = UnidadMedida::all()->keyBy('nombre');

        $presentacionesPorProducto = [
           
            'FER-TRI15-01' => [
                ['nombre' => 'Libra', 'factor_conversion' => 1,    'precio_venta' => 0.60, 'stock_minimo' => 200, 'es_base' => true],
                ['nombre' => 'Quintal', 'factor_conversion' => 100, 'precio_venta' => 55.00, 'stock_minimo' => 0, 'es_base' => false],
            ],
            'FER-URE-01' => [
                ['nombre' => 'Libra', 'factor_conversion' => 1,    'precio_venta' => 0.55, 'stock_minimo' => 200, 'es_base' => true],
                ['nombre' => 'Quintal', 'factor_conversion' => 100, 'precio_venta' => 50.00, 'stock_minimo' => 0, 'es_base' => false],
            ],
            'SEM-FRJ-01' => [
                ['nombre' => 'Libra', 'factor_conversion' => 1,    'precio_venta' => 0.85, 'stock_minimo' => 100, 'es_base' => true],
                ['nombre' => 'Quintal', 'factor_conversion' => 100, 'precio_venta' => 78.00, 'stock_minimo' => 0, 'es_base' => false],
            ],
            'RIE-MNG-01' => [
                ['nombre' => 'Metro', 'factor_conversion' => 1,      'precio_venta' => 0.75, 'stock_minimo' => 100, 'es_base' => true],
                ['nombre' => 'Rollo 50m', 'factor_conversion' => 50, 'precio_venta' => 35.00, 'stock_minimo' => 0, 'es_base' => false],
            ],
            'FRR-ALA-01' => [
                ['nombre' => 'Libra', 'factor_conversion' => 1,   'precio_venta' => 0.90, 'stock_minimo' => 50, 'es_base' => true],
                ['nombre' => 'Rollo 5lb', 'factor_conversion' => 5, 'precio_venta' => 4.25, 'stock_minimo' => 0, 'es_base' => false],
            ],
            'FRR-CLV-01' => [
                ['nombre' => 'Libra', 'factor_conversion' => 1,   'precio_venta' => 0.65, 'stock_minimo' => 50, 'es_base' => true],
            ],
            'ABO-BOC-01' => [
                ['nombre' => 'Libra', 'factor_conversion' => 1,    'precio_venta' => 0.35, 'stock_minimo' => 150, 'es_base' => true],
                ['nombre' => 'Quintal', 'factor_conversion' => 100, 'precio_venta' => 30.00, 'stock_minimo' => 0, 'es_base' => false],
            ],
            'ABO-HUM-01' => [
                ['nombre' => 'Libra', 'factor_conversion' => 1,    'precio_venta' => 0.40, 'stock_minimo' => 150, 'es_base' => true],
                ['nombre' => 'Quintal', 'factor_conversion' => 100, 'precio_venta' => 35.00, 'stock_minimo' => 0, 'es_base' => false],
            ],

           
            'INS-LOR-01' => [
                ['nombre' => 'Botella 1L', 'factor_conversion' => 1, 'precio_venta' => 8.50, 'stock_minimo' => 10, 'es_base' => false],
                ['nombre' => 'Envase 4L', 'factor_conversion' => 1, 'precio_venta' => 28.00, 'stock_minimo' => 5, 'es_base' => false],
            ],
            'FUN-CUP-01' => [
                ['nombre' => 'Bolsa 500g', 'factor_conversion' => 1, 'precio_venta' => 6.00, 'stock_minimo' => 15, 'es_base' => false],
                ['nombre' => 'Bolsa 1kg', 'factor_conversion' => 1, 'precio_venta' => 10.50, 'stock_minimo' => 20, 'es_base' => false],
            ],
            'SEM-MAI59-01' => [
                ['nombre' => 'Bolsa 1lb', 'factor_conversion' => 1, 'precio_venta' => 1.10, 'stock_minimo' => 100, 'es_base' => false],
                ['nombre' => 'Saco 50lb', 'factor_conversion' => 1, 'precio_venta' => 50.00, 'stock_minimo' => 0, 'es_base' => false],
            ],
            'HER-MC22-01' => [
                ['nombre' => 'Pieza', 'factor_conversion' => 1, 'precio_venta' => 9.60, 'stock_minimo' => 5, 'es_base' => false],
            ],
            'HER-PLA-01' => [
                ['nombre' => 'Pieza', 'factor_conversion' => 1, 'precio_venta' => 12.50, 'stock_minimo' => 5, 'es_base' => false],
            ],
            'RIE-ASP-01' => [
                ['nombre' => 'Pieza', 'factor_conversion' => 1, 'precio_venta' => 4.50, 'stock_minimo' => 8, 'es_base' => false],
            ],
            'REP-DIS-01' => [
                ['nombre' => 'Pieza', 'factor_conversion' => 1, 'precio_venta' => 35.00, 'stock_minimo' => 3, 'es_base' => false],
            ],
            'REP-CUC-01' => [
                ['nombre' => 'Pieza', 'factor_conversion' => 1, 'precio_venta' => 6.75, 'stock_minimo' => 5, 'es_base' => false],
            ],
        ];

        foreach ($presentacionesPorProducto as $codigoProducto => $presentaciones) {
            $producto = Producto::where('codigo', $codigoProducto)->firstOrFail();

            foreach ($presentaciones as $pres) {
                
                if ($producto->tipo_producto === 'GRANEL') {
                    
                    $unidad = $producto->unidadMedida;
                } else {
                    
                    $unidad = $unidades->get('Unidad') ?? $unidades->get('Pieza') ?? $producto->unidadMedida;
                }

               
                $stockMinimo = $pres['stock_minimo'] ?? 0;
                if ($producto->tipo_producto === 'GRANEL' && !($pres['es_base'] ?? false)) {
                    $stockMinimo = 0;
                }

                Presentacion::firstOrCreate(
                    [
                        'producto_id' => $producto->id,
                        'nombre' => $pres['nombre'],
                    ],
                    [
                        'unidad_medida_id' => $unidad->id,
                        'factor_conversion' => $pres['factor_conversion'],
                        'precio_venta' => $pres['precio_venta'],
                        'stock_minimo' => $stockMinimo,
                        'es_base' => $pres['es_base'] ?? false,
                        'activo' => true,
                    ]
                );
            }
        }
    }
}
<?php

namespace Database\Seeders;

use App\Models\CodigoBarra;
use App\Models\Presentacion;
use App\Models\Producto;
use Illuminate\Database\Seeder;

class CodigoBarraSeeder extends Seeder
{
    public function run(): void
    {
        $codigosBarras = [
            ['codigo_producto' => 'FER-TRI15-01', 'presentacion' => 'Libra', 'barcode' => '7501234500011'],
            ['codigo_producto' => 'FER-TRI15-01', 'presentacion' => 'Quintal', 'barcode' => '7501234500028'],

            ['codigo_producto' => 'FER-URE-01', 'presentacion' => 'Libra', 'barcode' => '7501234500035'],
            ['codigo_producto' => 'FER-URE-01', 'presentacion' => 'Quintal', 'barcode' => '7501234500042'],

            ['codigo_producto' => 'INS-LOR-01', 'presentacion' => 'Litro', 'barcode' => '7501234500059'],
            ['codigo_producto' => 'INS-LOR-01', 'presentacion' => 'Galón', 'barcode' => '7501234500066'],

            ['codigo_producto' => 'FUN-CUP-01', 'presentacion' => 'Kilogramo', 'barcode' => '7501234500073'],
            ['codigo_producto' => 'FUN-CUP-01', 'presentacion' => 'Bolsa 500g', 'barcode' => '7501234500080'],

            ['codigo_producto' => 'SEM-MAI59-01', 'presentacion' => 'Libra', 'barcode' => '7501234500097'],
            ['codigo_producto' => 'SEM-MAI59-01', 'presentacion' => 'Saco 50lb', 'barcode' => '7501234500103'],

            ['codigo_producto' => 'SEM-FRJ-01', 'presentacion' => 'Libra', 'barcode' => '7501234500110'],
            ['codigo_producto' => 'SEM-FRJ-01', 'presentacion' => 'Quintal', 'barcode' => '7501234500127'],

            ['codigo_producto' => 'HER-MC22-01', 'presentacion' => 'Unidad', 'barcode' => '7501234500134'],
            ['codigo_producto' => 'HER-PLA-01', 'presentacion' => 'Unidad', 'barcode' => '7501234500141'],

            ['codigo_producto' => 'RIE-MNG-01', 'presentacion' => 'Metro', 'barcode' => '7501234500158'],
            ['codigo_producto' => 'RIE-MNG-01', 'presentacion' => 'Rollo 100m', 'barcode' => '7501234500165'],

            ['codigo_producto' => 'RIE-ASP-01', 'presentacion' => 'Unidad', 'barcode' => '7501234500172'],

            ['codigo_producto' => 'FRR-ALA-01', 'presentacion' => 'Libra', 'barcode' => '7501234500189'],
            ['codigo_producto' => 'FRR-ALA-01', 'presentacion' => 'Rollo 5lb', 'barcode' => '7501234500196'],

            ['codigo_producto' => 'FRR-CLV-01', 'presentacion' => 'Libra', 'barcode' => '7501234500202'],

            ['codigo_producto' => 'REP-DIS-01', 'presentacion' => 'Unidad', 'barcode' => '7501234500219'],
            ['codigo_producto' => 'REP-CUC-01', 'presentacion' => 'Unidad', 'barcode' => '7501234500226'],

            ['codigo_producto' => 'ABO-BOC-01', 'presentacion' => 'Libra', 'barcode' => '7501234500233'],
            ['codigo_producto' => 'ABO-BOC-01', 'presentacion' => 'Quintal', 'barcode' => '7501234500240'],

            ['codigo_producto' => 'ABO-HUM-01', 'presentacion' => 'Libra', 'barcode' => '7501234500257'],
            ['codigo_producto' => 'ABO-HUM-01', 'presentacion' => 'Quintal', 'barcode' => '7501234500264'],
        ];

        foreach ($codigosBarras as $item) {
            $producto = Producto::where('codigo', $item['codigo_producto'])->firstOrFail();

            $presentacion = Presentacion::where('producto_id', $producto->id)
                ->where('nombre', $item['presentacion'])
                ->firstOrFail();

            CodigoBarra::firstOrCreate(
                ['codigo' => $item['barcode']],
                [
                    'activo' => true,
                    'presentacion_id' => $presentacion->id,
                ]
            );
        }
    
    }
}


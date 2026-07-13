<?php

namespace Database\Seeders;

use App\Models\Categoria;
use App\Models\Producto;
use App\Models\User;
use Illuminate\Database\Seeder;

class ProductoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
         $usuario = User::where('email', 'admin@test.com')->firstOrFail();

        $productosPorCategoria = [
            'Fertilizantes' => [
                ['codigo' => 'FER-TRI15-01', 'nombre' => 'Fertilizante Triple 15', 'fabricante' => 'Fertica', 'tipo_producto' => 'GRANEL', 'unidad_base' => 'Libra', 'aplica_iva' => true],
                ['codigo' => 'FER-URE-01', 'nombre' => 'Urea Agrícola 46%', 'fabricante' => 'Fertica', 'tipo_producto' => 'GRANEL', 'unidad_base' => 'Libra', 'aplica_iva' => true],
            ],
            'Insecticidas y Fungicidas' => [
                ['codigo' => 'INS-LOR-01', 'nombre' => 'Insecticida Lorsban 4E', 'fabricante' => 'Corteva', 'tipo_producto' => 'UNIDAD FIJA', 'unidad_base' => 'Litro', 'aplica_iva' => true],
                ['codigo' => 'FUN-CUP-01', 'nombre' => 'Fungicida Cupravit', 'fabricante' => 'Bayer', 'tipo_producto' => 'UNIDAD FIJA', 'unidad_base' => 'Kilogramo', 'aplica_iva' => true],
            ],
            'Semillas' => [
                ['codigo' => 'SEM-MAI59-01', 'nombre' => 'Semilla de Maíz H-59', 'fabricante' => 'Semillas Cristiani', 'tipo_producto' => 'UNIDAD FIJA', 'unidad_base' => 'Libra', 'aplica_iva' => false],
                ['codigo' => 'SEM-FRJ-01', 'nombre' => 'Semilla de Frijol Rojo', 'fabricante' => 'Semillas Cristiani', 'tipo_producto' => 'GRANEL', 'unidad_base' => 'Libra', 'aplica_iva' => false],
            ],
            'Herramientas Manuales' => [
                ['codigo' => 'HER-MC22-01', 'nombre' => 'Machete Corvo 22"', 'fabricante' => 'Imusa', 'tipo_producto' => 'UNIDAD FIJA', 'unidad_base' => 'Unidad', 'aplica_iva' => true],
                ['codigo' => 'HER-PLA-01', 'nombre' => 'Pala Punta Cuadrada', 'fabricante' => 'Truper', 'tipo_producto' => 'UNIDAD FIJA', 'unidad_base' => 'Unidad', 'aplica_iva' => true],
            ],
            'Equipo de Riego' => [
                ['codigo' => 'RIE-MNG-01', 'nombre' => 'Manguera para Riego 1/2"', 'fabricante' => 'Plycem', 'tipo_producto' => 'GRANEL', 'unidad_base' => 'Metro', 'aplica_iva' => true],
                ['codigo' => 'RIE-ASP-01', 'nombre' => 'Aspersor de Impacto', 'fabricante' => 'Rain Bird', 'tipo_producto' => 'UNIDAD FIJA', 'unidad_base' => 'Unidad', 'aplica_iva' => true],
            ],
            'Ferretería General' => [
                ['codigo' => 'FRR-ALA-01', 'nombre' => 'Alambre de Amarre', 'fabricante' => 'Cogesa', 'tipo_producto' => 'GRANEL', 'unidad_base' => 'Libra', 'aplica_iva' => true],
                ['codigo' => 'FRR-CLV-01', 'nombre' => 'Clavo de Lámina 2"', 'fabricante' => 'Cogesa', 'tipo_producto' => 'GRANEL', 'unidad_base' => 'Libra', 'aplica_iva' => true],
            ],
            'Repuestos Agrícolas' => [
                ['codigo' => 'REP-DIS-01', 'nombre' => 'Disco para Arado 24"', 'fabricante' => 'John Deere', 'tipo_producto' => 'UNIDAD FIJA', 'unidad_base' => 'Unidad', 'aplica_iva' => true],
                ['codigo' => 'REP-CUC-01', 'nombre' => 'Cuchilla para Bomba de Motor', 'fabricante' => 'Stihl', 'tipo_producto' => 'UNIDAD FIJA', 'unidad_base' => 'Unidad', 'aplica_iva' => true],
            ],
            'Abonos Orgánicos' => [
                ['codigo' => 'ABO-BOC-01', 'nombre' => 'Abono Orgánico Bocashi', 'fabricante' => 'AgroNatura', 'tipo_producto' => 'GRANEL', 'unidad_base' => 'Libra', 'aplica_iva' => false],
                ['codigo' => 'ABO-HUM-01', 'nombre' => 'Humus de Lombriz', 'fabricante' => 'AgroNatura', 'tipo_producto' => 'GRANEL', 'unidad_base' => 'Libra', 'aplica_iva' => false],
            ],
        ];

        foreach ($productosPorCategoria as $nombreCategoria => $productos) {
            $categoria = Categoria::where('nombre', $nombreCategoria)->firstOrFail();

            foreach ($productos as $producto) {
                Producto::firstOrCreate(
                    ['codigo' => $producto['codigo']],
                    [
                        'nombre' => $producto['nombre'],
                        'fabricante' => $producto['fabricante'],
                        'tipo_producto' => $producto['tipo_producto'],
                        'unidad_base' => $producto['unidad_base'],
                        'aplica_iva' => $producto['aplica_iva'],
                        'categoria_id' => $categoria->id,
                        'registrado_por' => $usuario->id,
                    ]
                );
            }
        }
    }
}

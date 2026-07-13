<?php

namespace Database\Seeders;

use App\Models\Compra;
use App\Models\DetalleCompra;
use App\Models\Lote;
use App\Models\Presentacion;
use App\Models\Producto;
use App\Models\Proveedor;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CompraSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
         $usuario = User::where('email', 'admin@test.com')->firstOrFail();

        $compras = [
            [
                'proveedor' => 'Fertica El Salvador',
                'tipo_dte' => '03',
                'numero_documento' => 'DTE-03-M001P001-000000000000001',
                'fecha_emision' => now()->subDays(20)->toDateString(),
                'estado_pago' => 'PAGADO',
                'fecha_vencimiento_pago' => null,
                'items' => [
                    ['codigo_producto' => 'FER-TRI15-01', 'presentacion' => 'Quintal', 'cantidad_facturada' => 2, 'precio_unitario_factura' => 45.00],
                    ['codigo_producto' => 'FER-URE-01', 'presentacion' => 'Quintal', 'cantidad_facturada' => 3, 'precio_unitario_factura' => 42.00],
                ],
            ],
            [
                'proveedor' => 'Distribuidora Agrícola Corteva',
                'tipo_dte' => '03',
                'numero_documento' => 'DTE-03-M001P001-000000000000002',
                'fecha_emision' => now()->subDays(10)->toDateString(),
                'estado_pago' => 'PENDIENTE',
                'fecha_vencimiento_pago' => now()->addDays(20)->toDateString(),
                'items' => [
                    ['codigo_producto' => 'INS-LOR-01', 'presentacion' => 'Galón', 'cantidad_facturada' => 5, 'precio_unitario_factura' => 25.00],
                    ['codigo_producto' => 'FUN-CUP-01', 'presentacion' => 'Kilogramo', 'cantidad_facturada' => 4, 'precio_unitario_factura' => 5.50],
                ],
            ],
            [
                'proveedor' => 'Ferretería y Suministros Truper SV',
                'tipo_dte' => '01',
                'numero_documento' => 'DTE-01-M001P001-000000000000001',
                'fecha_emision' => now()->subDays(5)->toDateString(),
                'estado_pago' => 'PAGADO',
                'fecha_vencimiento_pago' => null,
                'items' => [
                    ['codigo_producto' => 'HER-MC22-01', 'presentacion' => 'Unidad', 'cantidad_facturada' => 10, 'precio_unitario_factura' => 7.80],
                    ['codigo_producto' => 'HER-PLA-01', 'presentacion' => 'Unidad', 'cantidad_facturada' => 8, 'precio_unitario_factura' => 10.00],
                ],
            ],
        ];

        foreach ($compras as $datosCompra) {
            $proveedor = Proveedor::where('nombre', $datosCompra['proveedor'])->firstOrFail();

            $montoTotal = 0;
            foreach ($datosCompra['items'] as $item) {
                $montoTotal += $item['cantidad_facturada'] * $item['precio_unitario_factura'];
            }

            $compra = Compra::firstOrCreate(
                ['numero_documento' => $datosCompra['numero_documento']],
                [
                    'tipo_dte' => $datosCompra['tipo_dte'],
                    'es_anulado' => false,
                    'fecha_emision' => $datosCompra['fecha_emision'],
                    'descuento_global' => null,
                    'iva_total' => null,
                    'monto_total' => $montoTotal,
                    'estado_pago' => $datosCompra['estado_pago'],
                    'fecha_vencimiento_pago' => $datosCompra['fecha_vencimiento_pago'],
                    'proveedor_id' => $proveedor->id,
                    'usuario_id' => $usuario->id,
                ]
            );

            foreach ($datosCompra['items'] as $item) {
                $producto = Producto::where('codigo', $item['codigo_producto'])->firstOrFail();

                $presentacion = Presentacion::where('producto_id', $producto->id)
                    ->where('nombre', $item['presentacion'])
                    ->firstOrFail();

                $subTotal = round($item['cantidad_facturada'] * $item['precio_unitario_factura'], 2);
                $cantidadInicial = $item['cantidad_facturada'] * $presentacion->factor_conversion;
                $costoUnitarioCompra = round($item['precio_unitario_factura'] / $presentacion->factor_conversion, 4);

                $lote = Lote::create([
                    'lote_interno' => $this->generarLoteInterno(),
                    'lote_fabricante' => null,
                    'fecha_vencimiento' => null,
                    'cantidad_inicial' => $cantidadInicial,
                    'cantidad_actual' => $cantidadInicial,
                    'costo_unitario_compra' => $costoUnitarioCompra,
                    'porcentaje_descuento' => null,
                    'estado' => 'ACTIVO',
                    'presentacion_id' => $presentacion->id,
                ]);

                DetalleCompra::create([
                    'es_anulado' => false,
                    'cantidad_facturada' => $item['cantidad_facturada'],
                    'cantidad_bonificada' => 0,
                    'precio_unitario_factura' => $item['precio_unitario_factura'],
                    'iva_linea' => null,
                    'descuento_linea' => 0,
                    'sub_total' => $subTotal,
                    'compra_id' => $compra->id,
                    'lote_id' => $lote->id,
                ]);
            }
        }
    }

    private function generarLoteInterno()
    {
        $fecha = now()->format('Ymd');

        $resultado = DB::select(
            'SELECT lote_interno FROM lotes
             WHERE lote_interno LIKE :buscar
             ORDER BY lote_interno DESC
             LIMIT 1',
            ['buscar' => "LOT-{$fecha}-%"]
        );

        $ultimo = ! empty($resultado) ? $resultado[0]->lote_interno : null;

        if ($ultimo) {
            $secuencia = (int) substr($ultimo, -4) + 1;
        } else {
            $secuencia = 1;
        }

        return 'LOT-'.$fecha.'-'.str_pad($secuencia, 4, '0', STR_PAD_LEFT);
    }
}

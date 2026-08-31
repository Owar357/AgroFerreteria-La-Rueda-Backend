<?php

namespace Database\Seeders;

use App\Models\Compra;
use App\Models\DetalleCompra;
use App\Models\Lote;
use App\Models\Presentacion;
use App\Models\Producto;
use App\Models\Proveedor;
use App\Models\User;
use App\Services\KardexService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class CompraSeeder extends Seeder
{
    public function run(KardexService $kardexService): void
    {
        $usuario = User::where('email', 'admin@test.com')->firstOrFail();

        $compras = [
            [
                'proveedor' => 'Fertica El Salvador',
                'tipo_dte' => '03',
                'numero_documento' => 'DTE-03-001-000000001',
                'fecha_emision' => now()->subDays(20)->toDateString(),
                'estado_pago' => 'PAGADO',
                'fecha_vencimiento_pago' => null,
                'items' => [
                    ['codigo' => 'FER-TRI15-01', 'presentacion' => 'Quintal', 'cantidad' => 10, 'precio' => 45.00],
                    ['codigo' => 'FER-URE-01',   'presentacion' => 'Quintal', 'cantidad' => 15, 'precio' => 42.00],
                ],
                'fecha_vencimiento_lotes' => Carbon::now()->addMonths(12),
            ],
            [
                'proveedor' => 'Distribuidora Agrícola Corteva',
                'tipo_dte' => '03',
                'numero_documento' => 'DTE-03-001-000000002',
                'fecha_emision' => now()->subDays(10)->toDateString(),
                'estado_pago' => 'PENDIENTE',
                'fecha_vencimiento_pago' => now()->addDays(5)->toDateString(),
                'items' => [
                    ['codigo' => 'INS-LOR-01', 'presentacion' => 'Envase 4L', 'cantidad' => 20, 'precio' => 25.00],
                    ['codigo' => 'INS-LOR-01', 'presentacion' => 'Botella 1L', 'cantidad' => 30, 'precio' => 7.00],
                    ['codigo' => 'FUN-CUP-01', 'presentacion' => 'Bolsa 1kg', 'cantidad' => 25, 'precio' => 8.50],
                ],
                'fecha_vencimiento_lotes' => Carbon::now()->addMonths(6),
            ],
            [
                'proveedor' => 'Semillas Cristiani',
                'tipo_dte' => '03',
                'numero_documento' => 'DTE-03-001-000000003',
                'fecha_emision' => now()->subDays(15)->toDateString(),
                'estado_pago' => 'PAGADO',
                'fecha_vencimiento_pago' => null,
                'items' => [
                    ['codigo' => 'SEM-MAI59-01', 'presentacion' => 'Saco 50lb', 'cantidad' => 10, 'precio' => 40.00],
                    ['codigo' => 'SEM-MAI59-01', 'presentacion' => 'Bolsa 1lb',  'cantidad' => 100, 'precio' => 0.80],
                    ['codigo' => 'SEM-FRJ-01',   'presentacion' => 'Quintal',   'cantidad' => 20, 'precio' => 65.00],
                ],
                'fecha_vencimiento_lotes' => Carbon::now()->addMonths(9),
            ],
            [
                'proveedor' => 'Ferretería y Suministros Truper SV',
                'tipo_dte' => '01',
                'numero_documento' => 'DTE-01-001-000000004',
                'fecha_emision' => now()->subDays(12)->toDateString(),
                'estado_pago' => 'PAGADO',
                'fecha_vencimiento_pago' => null,
                'items' => [
                    ['codigo' => 'HER-MC22-01', 'presentacion' => 'Pieza', 'cantidad' => 25, 'precio' => 7.00],
                    ['codigo' => 'HER-PLA-01',  'presentacion' => 'Pieza', 'cantidad' => 15, 'precio' => 9.50],
                    ['codigo' => 'RIE-ASP-01',  'presentacion' => 'Pieza', 'cantidad' => 30, 'precio' => 3.00],
                ],
                'fecha_vencimiento_lotes' => null, 
            ],
            [
                'proveedor' => 'AgroNatura',
                'tipo_dte' => '03',
                'numero_documento' => 'DTE-03-001-000000005',
                'fecha_emision' => now()->subDays(5)->toDateString(),
                'estado_pago' => 'PAGADO',
                'fecha_vencimiento_pago' => null,
                'items' => [
                    ['codigo' => 'ABO-BOC-01', 'presentacion' => 'Quintal', 'cantidad' => 15, 'precio' => 22.00],
                    ['codigo' => 'ABO-HUM-01', 'presentacion' => 'Quintal', 'cantidad' => 15, 'precio' => 26.00],
                ],
                'fecha_vencimiento_lotes' => Carbon::now()->addMonths(5),
            ],
            [
                'proveedor' => 'Bayer',
                'tipo_dte' => '03',
                'numero_documento' => 'DTE-03-001-000000006',
                'fecha_emision' => now()->subDays(3)->toDateString(),
                'estado_pago' => 'ABONADO',
                'fecha_vencimiento_pago' => now()->addDays(15)->toDateString(),
                'items' => [
                    ['codigo' => 'RIE-MNG-01', 'presentacion' => 'Rollo 50m', 'cantidad' => 10, 'precio' => 25.00],
                    ['codigo' => 'FRR-ALA-01', 'presentacion' => 'Rollo 5lb',  'cantidad' => 20, 'precio' => 3.20],
                    ['codigo' => 'FRR-CLV-01', 'presentacion' => 'Libra',      'cantidad' => 200, 'precio' => 0.45],
                    ['codigo' => 'REP-DIS-01', 'presentacion' => 'Pieza',      'cantidad' => 8,  'precio' => 28.00],
                    ['codigo' => 'REP-CUC-01', 'presentacion' => 'Pieza',      'cantidad' => 15, 'precio' => 4.50],
                ],
                'fecha_vencimiento_lotes' => null,
            ],
           
            [
                'proveedor' => 'Semillas Cristiani',
                'tipo_dte' => '03',
                'numero_documento' => 'DTE-03-001-000000007',
                'fecha_emision' => now()->subDays(2)->toDateString(),
                'estado_pago' => 'PAGADO',
                'fecha_vencimiento_pago' => null,
                'items' => [
                    ['codigo' => 'SEM-FRJ-01', 'presentacion' => 'Quintal', 'cantidad' => 5, 'precio' => 60.00, 'descuento_promo' => 10.00],
                ],
                'fecha_vencimiento_lotes' => Carbon::now()->addDays(20), // Próximo a vencer
            ],
        ];

        foreach ($compras as $datos) {
            $proveedor = Proveedor::where('nombre', $datos['proveedor'])->first();

            if (! $proveedor) {
                $proveedor = Proveedor::first();
            }

            $montoTotal = 0;
            foreach ($datos['items'] as $item) {
                $montoTotal += $item['cantidad'] * $item['precio'];
            }

            $compra = Compra::firstOrCreate(
                ['numero_documento' => $datos['numero_documento']],
                [
                    'tipo_dte'               => $datos['tipo_dte'],
                    'es_anulado'             => false,
                    'fecha_emision'          => $datos['fecha_emision'],
                    'descuento_global'       => null,
                    'iva_total'              => null,
                    'monto_total'            => $montoTotal,
                    'estado_pago'            => $datos['estado_pago'],
                    'fecha_vencimiento_pago' => $datos['fecha_vencimiento_pago'],
                    'proveedor_id'           => $proveedor->id,
                    'usuario_id'             => $usuario->id,
                ]
            );

            foreach ($datos['items'] as $item) {
                $producto = Producto::where('codigo', $item['codigo'])->firstOrFail();
                $presentacion = Presentacion::where('producto_id', $producto->id)
                    ->where('nombre', $item['presentacion'])
                    ->firstOrFail();

                $esGranel = ($producto->tipo_producto === 'GRANEL');
                $subTotal = round($item['cantidad'] * $item['precio'], 2);
                $factorConversion = (float) ($presentacion->factor_conversion ?? 1);
                
                // Cantidad inicial convertida a unidades base (para Granel) o unidades directas
                $cantidadInicial = $esGranel
                    ? $item['cantidad'] * $factorConversion
                    : $item['cantidad'];

                $costoUnitarioCompra = round($item['precio'] / $factorConversion, 4);

                // ✅ SE GUARDA TANTO EL PRODUCTO COMO LA PRESENTACIÓN PARA COMPATIBILIDAD TOTAL
                $lote = Lote::create([
                    'lote_interno'          => $this->generarLoteInterno(),
                    'lote_fabricante'       => 'FAB-' . rand(1000, 9999),
                    'fecha_vencimiento'     => $datos['fecha_vencimiento_lotes'] ?? null,
                    'cantidad_inicial'      => $cantidadInicial,
                    'cantidad_actual'       => $cantidadInicial,
                    'costo_unitario_compra' => $costoUnitarioCompra,
                    'porcentaje_descuento'  => $item['descuento_promo'] ?? null,
                    'estado'                => 'ACTIVO',
                    'producto_id'           => $producto->id,     
                    'presentacion_id'       => $presentacion->id, 
                ]);

                DetalleCompra::create([
                    'es_anulado'              => false,
                    'cantidad_facturada'      => $item['cantidad'],
                    'cantidad_bonificada'     => 0,
                    'precio_unitario_factura' => $item['precio'],
                    'iva_linea'               => null,
                    'descuento_linea'         => 0,
                    'sub_total'               => $subTotal,
                    'compra_id'               => $compra->id,
                    'lote_id'                 => $lote->id,
                ]);

                
                $kardexService->registrarEntrada(
                    $presentacion,
                    $lote,
                    (float) $item['cantidad'],
                    $compra,
                    $compra->numero_documento,
                    'Compra Seeder ' . $compra->numero_documento
                );
            }
        }
    }

    private function generarLoteInterno(): string
    {
        $fecha = now()->format('Ymd');
        $resultado = DB::select(
            'SELECT lote_interno FROM lotes
             WHERE lote_interno LIKE :buscar
             ORDER BY lote_interno DESC
             LIMIT 1',
            ['buscar' => "LOT-{$fecha}-%"]
        );
        $ultimo = !empty($resultado) ? $resultado[0]->lote_interno : null;
        if ($ultimo) {
            $secuencia = (int) substr($ultimo, -4) + 1;
        } else {
            $secuencia = 1;
        }
        return 'LOT-' . $fecha . '-' . str_pad($secuencia, 4, '0', STR_PAD_LEFT);
    }
}
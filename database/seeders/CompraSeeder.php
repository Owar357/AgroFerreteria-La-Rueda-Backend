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
    // 🔹 Inyectamos KardexService en el método run
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
                    ['codigo' => 'FER-TRI15-01', 'presentacion' => 'Quintal', 'cantidad' => 2, 'precio' => 45.00],
                    ['codigo' => 'FER-URE-01',   'presentacion' => 'Quintal', 'cantidad' => 3, 'precio' => 42.00],
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
                    ['codigo' => 'INS-LOR-01', 'presentacion' => 'Envase 4L', 'cantidad' => 5, 'precio' => 25.00],
                    ['codigo' => 'FUN-CUP-01', 'presentacion' => 'Bolsa 1kg', 'cantidad' => 4, 'precio' => 5.50],
                ],
                'fecha_vencimiento_lotes' => Carbon::now()->addMonths(6),
            ],
            [
                'proveedor' => 'Ferretería y Suministros Truper SV',
                'tipo_dte' => '01',
                'numero_documento' => 'DTE-01-001-000000003',
                'fecha_emision' => now()->subDays(15)->toDateString(),
                'estado_pago' => 'PENDIENTE',
                'fecha_vencimiento_pago' => now()->subDays(2)->toDateString(),
                'items' => [
                    ['codigo' => 'HER-MC22-01', 'presentacion' => 'Pieza', 'cantidad' => 10, 'precio' => 7.80],
                ],
                'fecha_vencimiento_lotes' => Carbon::now()->addMonths(6),
            ],
            [
                'proveedor' => 'Semillas Cristiani',
                'tipo_dte' => '03',
                'numero_documento' => 'DTE-03-001-000000004',
                'fecha_emision' => now()->subDays(5)->toDateString(),
                'estado_pago' => 'ABONADO',
                'fecha_vencimiento_pago' => now()->addDays(3)->toDateString(),
                'items' => [
                    ['codigo' => 'SEM-MAI59-01', 'presentacion' => 'Bolsa 1lb', 'cantidad' => 50, 'precio' => 1.20],
                ],
                'fecha_vencimiento_lotes' => Carbon::now()->addMonths(8),
            ],
            [
                'proveedor' => 'AgroNatura',
                'tipo_dte' => '03',
                'numero_documento' => 'DTE-03-001-000000005',
                'fecha_emision' => now()->subDays(2)->toDateString(),
                'estado_pago' => 'PAGADO',
                'fecha_vencimiento_pago' => null,
                'items' => [
                    ['codigo' => 'ABO-BOC-01', 'presentacion' => 'Libra', 'cantidad' => 30, 'precio' => 0.80],
                ],
                'fecha_vencimiento_lotes' => Carbon::now()->addDays(15),
            ],
            [
                'proveedor' => 'Bayer',
                'tipo_dte' => '03',
                'numero_documento' => 'DTE-03-001-000000006',
                'fecha_emision' => now()->subDays(10)->toDateString(),
                'estado_pago' => 'PAGADO',
                'fecha_vencimiento_pago' => null,
                'items' => [
                    ['codigo' => 'FUN-CUP-01', 'presentacion' => 'Bolsa 500g', 'cantidad' => 10, 'precio' => 5.50],
                ],
                'fecha_vencimiento_lotes' => Carbon::now()->subDays(3),
            ],
        ];

        foreach ($compras as $datos) {
            $proveedor = Proveedor::where('nombre', $datos['proveedor'])->firstOrFail();

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

                $subTotal = round($item['cantidad'] * $item['precio'], 2);
                $cantidadInicial = $item['cantidad'] * $presentacion->factor_conversion;
                $costoUnitarioCompra = round($item['precio'] / $presentacion->factor_conversion, 4);

                $lote = Lote::create([
                    'lote_interno'          => $this->generarLoteInterno(),
                    'lote_fabricante'       => null,
                    'fecha_vencimiento'     => $datos['fecha_vencimiento_lotes'] ?? null,
                    'cantidad_inicial'      => $cantidadInicial,
                    'cantidad_actual'       => $cantidadInicial,
                    'costo_unitario_compra' => $costoUnitarioCompra,
                    'porcentaje_descuento'  => null,
                    'estado'                => 'ACTIVO',
                    'presentacion_id'       => $presentacion->id,
                ]);

                DetalleCompra::create([
                    'es_anulado'             => false,
                    'cantidad_facturada'     => $item['cantidad'],
                    'cantidad_bonificada'    => 0,
                    'precio_unitario_factura' => $item['precio'],
                    'iva_linea'              => null,
                    'descuento_linea'        => 0,
                    'sub_total'              => $subTotal,
                    'compra_id'              => $compra->id,
                    'lote_id'                => $lote->id,
                ]);

                // 🔹 IMPACTO EN EL KARDEX: Registra la entrada física
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

        $this->ajustarStockParaAlertas();
    }

    private function ajustarStockParaAlertas(): void
    {
        $producto = Producto::where('codigo', 'FER-TRI15-01')->first();
        if ($producto) {
            $presentacionBase = $producto->presentaciones()->where('stock_minimo', '>', 0)->first();
            if ($presentacionBase) {
                Lote::where('presentacion_id', $presentacionBase->id)
                    ->update(['cantidad_actual' => 50]);
            }
        }

        $productoAgotado = Producto::where('codigo', 'INS-LOR-01')->first();
        if ($productoAgotado) {
            $presentacion = $productoAgotado->presentaciones()->where('nombre', 'Envase 4L')->first();
            if ($presentacion) {
                Lote::where('presentacion_id', $presentacion->id)
                    ->update(['cantidad_actual' => 0]);
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
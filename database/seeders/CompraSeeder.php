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
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class CompraSeeder extends Seeder
{
    public function run(): void
    {
        

        $usuario = User::where('email', 'admin@test.com')->firstOrFail();

        $this->crearPresentaciones();


        $compras = [
            // --- CASO 1: Compra PAGADA (sin alerta) ---
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
                    ['codigo' => 'INS-LOR-01', 'presentacion' => 'Galón', 'cantidad' => 5, 'precio' => 25.00],
                    ['codigo' => 'FUN-CUP-01', 'presentacion' => 'Kilogramo', 'cantidad' => 4, 'precio' => 5.50],
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
                    ['codigo' => 'HER-MC22-01', 'presentacion' => 'Unidad', 'cantidad' => 10, 'precio' => 7.80],
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
                    ['codigo' => 'SEM-MAI59-01', 'presentacion' => 'Libra', 'cantidad' => 50, 'precio' => 1.20],
                ],
                'fecha_vencimiento_lotes' => Carbon::now()->addMonths(8),
            ],

          
            [
                'proveedor' => 'AgroNatura',
                'tipo_dte' => '03',
                'numero_documento' => 'DTE-03-001-000000005',
                'fecha_emision' => now()->subDays(2)->toDateString(),
                'estado_pago' => 'PAGADO', // no genera alerta de compra
                'fecha_vencimiento_pago' => null,
                'items' => [
                    ['codigo' => 'ABO-BOC-01', 'presentacion' => 'Libra', 'cantidad' => 30, 'precio' => 0.80],
                ],
                'fecha_vencimiento_lotes' => Carbon::now()->addDays(15), // ¡por vencer!
            ],

           
            [
                'proveedor' => 'Bayer',
                'tipo_dte' => '03',
                'numero_documento' => 'DTE-03-001-000000006',
                'fecha_emision' => now()->subDays(10)->toDateString(),
                'estado_pago' => 'PAGADO',
                'fecha_vencimiento_pago' => null,
                'items' => [
                    ['codigo' => 'FUN-CUP-01', 'presentacion' => 'Kilogramo', 'cantidad' => 10, 'precio' => 5.50],
                ],
                'fecha_vencimiento_lotes' => Carbon::now()->subDays(3), // ya vencido
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
                    'cantidad_actual'       => $cantidadInicial, // inicialmente igual
                    'costo_unitario_compra' => $costoUnitarioCompra,
                    'porcentaje_descuento'  => null,
                    'estado'                => 'ACTIVO',
                    'presentacion_id'       => $presentacion->id,
                ]);

                DetalleCompra::create([
                    'es_anulado'             => false,
                    'cantidad_facturada'     => $item['cantidad'],
                    'cantidad_bonificada'    => 0,
                    'precio_unitario_factura'=> $item['precio'],
                    'iva_linea'              => null,
                    'descuento_linea'        => 0,
                    'sub_total'              => $subTotal,
                    'compra_id'              => $compra->id,
                    'lote_id'                => $lote->id,
                ]);
            }
        }

    
        $this->crearLotesSueltos($usuario);

        $this->ajustarStockParaAlertas();
    }

    

    private function crearPresentaciones(): void
    {
        // Definir para cada producto una o más presentaciones con factor de conversión
        // Usamos factor = 1 para simplificar (la cantidad en la compra ya está en la unidad base)
        $productos = Producto::all();

        $mapa = [
            'FER-TRI15-01' => ['nombre' => 'Quintal', 'factor' => 1],
            'FER-URE-01'   => ['nombre' => 'Quintal', 'factor' => 1],
            'INS-LOR-01'   => ['nombre' => 'Galón', 'factor' => 1],
            'FUN-CUP-01'   => ['nombre' => 'Kilogramo', 'factor' => 1],
            'SEM-MAI59-01' => ['nombre' => 'Libra', 'factor' => 1],
            'SEM-FRJ-01'   => ['nombre' => 'Libra', 'factor' => 1],
            'HER-MC22-01'  => ['nombre' => 'Unidad', 'factor' => 1],
            'HER-PLA-01'   => ['nombre' => 'Unidad', 'factor' => 1],
            'RIE-MNG-01'   => ['nombre' => 'Metro', 'factor' => 1],
            'RIE-ASP-01'   => ['nombre' => 'Unidad', 'factor' => 1],
            'FRR-ALA-01'   => ['nombre' => 'Libra', 'factor' => 1],
            'FRR-CLV-01'   => ['nombre' => 'Libra', 'factor' => 1],
            'REP-DIS-01'   => ['nombre' => 'Unidad', 'factor' => 1],
            'REP-CUC-01'   => ['nombre' => 'Unidad', 'factor' => 1],
            'ABO-BOC-01'   => ['nombre' => 'Libra', 'factor' => 1],
            'ABO-HUM-01'   => ['nombre' => 'Libra', 'factor' => 1],
        ];

        foreach ($productos as $producto) {
            $config = $mapa[$producto->codigo] ?? ['nombre' => 'Unidad', 'factor' => 1];
            Presentacion::firstOrCreate(
                ['producto_id' => $producto->id, 'nombre' => $config['nombre']],
                ['factor_conversion' => $config['factor']]
            );
        }
    }

    private function crearLotesSueltos($usuario): void
    {
       

        $productosSinLotes = Producto::whereNotIn('codigo', [
            'FER-TRI15-01', 'FER-URE-01', 'INS-LOR-01', 'FUN-CUP-01',
            'SEM-MAI59-01', 'HER-MC22-01', 'ABO-BOC-01'
        ])->get();

        foreach ($productosSinLotes as $producto) {
            $presentacion = $producto->presentaciones()->first();
            if (!$presentacion) continue;

         
            Lote::create([
                'lote_interno'          => 'LOT-SUELTO-' . $producto->codigo,
                'lote_fabricante'       => null,
                'fecha_vencimiento'     => Carbon::now()->addMonths(12),
                'cantidad_inicial'      => 100,
                'cantidad_actual'       => 100, // luego ajustaremos
                'costo_unitario_compra' => 1.0,
                'porcentaje_descuento'  => null,
                'estado'                => 'ACTIVO',
                'presentacion_id'       => $presentacion->id,
            ]);
        }

       
    }

    private function ajustarStockParaAlertas(): void
    {
        $productoBajo = Producto::where('codigo', 'FER-TRI15-01')->first();
        if ($productoBajo) {
            Lote::whereHas('presentacion', function ($q) use ($productoBajo) {
                $q->where('producto_id', $productoBajo->id);
            })->update(['cantidad_actual' => 50]); // stock bajo (50 < 200)
        }

        
        $productoNormal = Producto::where('codigo', 'SEM-MAI59-01')->first();
        if ($productoNormal) {
            Lote::whereHas('presentacion', function ($q) use ($productoNormal) {
                $q->where('producto_id', $productoNormal->id);
            })->update(['cantidad_actual' => 150]); 
        }

        
        $productoAgotado = Producto::where('codigo', 'INS-LOR-01')->first();
        if ($productoAgotado) {
            Lote::whereHas('presentacion', function ($q) use ($productoAgotado) {
                $q->where('producto_id', $productoAgotado->id);
            })->update(['cantidad_actual' => 0]);
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
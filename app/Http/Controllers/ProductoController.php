<?php

namespace App\Http\Controllers;

use App\Http\Requests\Producto\StoreProductoRequest;
use App\Http\Requests\Producto\UpdateProductoRequest;
use App\Models\Lote;
use App\Models\Presentacion;
use App\Models\Producto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductoController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
{
    try {
        if (! auth()->user()->hasAnyRole(['ADMIN', 'CAJERO'])) {
            return response()->json([
                'status' => 'error',
                'message' => 'No autorizado',
            ], 403);
        }

        $perPage = $request->input('per_page', 8);
        $page = $request->input('page', 1);
        $search = trim($request->input('q', ''));
        $categoria = $request->input('categoria', null);

        // Construcción de la consulta base
        $query = Producto::with(['categoria:id,nombre', 'unidadMedida:id,nombre,abreviatura'])
            ->select('id', 'codigo', 'nombre', 'fabricante', 'tipo_producto', 'unidad_medida_id', 'categoria_id');

        if (! empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('nombre', 'ILIKE', "%{$search}%")
                  ->orWhere('codigo', 'ILIKE', "%{$search}%")
                  ->orWhere('fabricante', 'ILIKE', "%{$search}%");
            });
        }

        if (! empty($categoria)) {
            $query->whereHas('categoria', function ($q) use ($categoria) {
                $q->where('nombre', $categoria);
            });
        }

        $productos = $query->orderBy('id', 'desc')
            ->paginate($perPage, ['*'], 'page', $page);

        return response()->json([
            'status' => 'ok',
            'data' => $productos->items(),
            'total' => $productos->total(),
            'per_page' => $productos->perPage(),
            'current_page' => $productos->currentPage(),
            'last_page' => $productos->lastPage(),
        ], 200);

    } catch (\Exception $e) {
        return response()->json([
            'status' => 'error',
            'message' => 'Error al obtener productos',
        ], 500);
    }
}
    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreProductoRequest $request)
    {
        try {

            if (! auth()->user()->hasRole('ADMIN')) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'No autorizado',
                ], 403);
            }

            DB::beginTransaction();

            $producto = Producto::create([
                ...$request->safe()->except('presentaciones'),
                'registrado_por' => auth()->id(),
            ]);

            foreach ($request->validated()['presentaciones'] as $presentacionData) {

                $presentacion = $producto->presentaciones()->create([
                    'nombre' => $presentacionData['nombre'],
                    'factor_conversion' => $presentacionData['factor_conversion'],
                    'precio_venta' => $presentacionData['precio_venta'],
                    'stock_minimo' => $presentacionData['stock_minimo'],
                    'es_base' => $presentacionData['es_base'],
                    'unidad_medida_id' => $presentacionData['unidad_medida_id'],
                ]);

                foreach ($presentacionData['codigos_barra'] ?? [] as $codigoData) {

                    $presentacion->codigosBarras()->create([
                        'codigo' => $codigoData['codigo'],
                    ]);
                }
            }

            DB::commit();

            return response()->json([
                'status' => 'ok',
                'message' => 'Producto creado exitosamente',
                'data' => $producto->load(
                    'presentaciones.codigosBarras', 'presentaciones.unidadMedida', 'unidadMedida',
                ),
            ], 201);
        } catch (\Exception $e) {

            DB::rollBack();

            return response()->json([
                'message' => 'Error al registrar el producto',
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        try {
            if (! auth()->user()->hasAnyRole(['ADMIN', 'CAJERO'])) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'No autorizado',
                ], 403);
            }

            $existeProducto = Producto::where('id', $id)->exists();

            if (! $existeProducto) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Producto no encontrado',
                ], 404);
            }

            $presentaciones = Presentacion::select([
                'id',
                'nombre',
                'factor_conversion',
                'precio_venta',
                'es_base',
                'activo',
                'producto_id',
                'unidad_medida_id',
                DB::raw('(
                    SELECT COALESCE(SUM(l.cantidad_actual), 0)
                    FROM lotes l
                    WHERE l.presentacion_id = presentaciones.id
                    AND l.estado = \'ACTIVO\'
                ) as stock_actual'),
            ])->with('producto:id', 'unidadMedida:id,nombre,abreviatura')
                ->where('producto_id', $id)
                ->orderBy('es_base', 'desc')
                ->orderBy('factor_conversion', 'asc')
                ->get();

            if ($presentaciones->isEmpty()) {
                return response()->json([
                    'status' => 'ok',
                    'data' => [],
                    'message' => 'No hay presentaciones registradas',
                ], 200);
            }

            return response()->json([
                'status' => 'ok',
                'data' => $presentaciones,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error interno del servidor',
            ], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateProductoRequest $request, string $id)
    {
        try {

            if (! auth()->user()->hasRole('ADMIN')) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'No autorizado',
                ], 403);
            }

            $producto = Producto::find($id);

            if (! $producto) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Producto no encontrado',
                ]);
            }

            $producto->update($request->validated());

            return response()->json([
                'status' => 'ok',
                'message' => 'Producto actulizado correctamente.',
                'Producto' => $producto->fresh(),
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error al actualizar el producto',
            ], 500);
        }
    }

    public function buscarVenta(Request $request)
    {
        try {
            $q = trim($request->input('q', ''));

            if (strlen($q) < 2) {
                return response()->json([]);
            }

            $productos = Producto::query()
                ->select('id', 'codigo', 'nombre', 'unidad_medida_id', 'aplica_iva', 'tipo_producto')
                ->with(['unidadMedida:id,nombre,abreviatura'])
                ->where(function ($query) use ($q) {
                    $query->where('nombre', 'ilike', "%{$q}%")
                        ->orWhere('codigo', 'ilike', "%{$q}%")
                        ->orWhereHas('presentaciones.codigosBarras', function ($sub) use ($q) {
                            $sub->where('codigo', 'ilike', "%{$q}%");
                        });
                })
                ->with(['presentaciones' => function ($query) {
                    $query->where('activo', true)
                        ->select('id', 'producto_id', 'nombre', 'factor_conversion', 'precio_venta');
                }])
                ->limit(15)
                ->get();

            $productosTransformados = $productos->map(function ($producto) {

                $queryLote = Lote::query()
                    ->where('cantidad_actual', '>', 0)
                    ->where('estado', 'ACTIVO');

                if ($producto->tipo_producto === 'GRANEL') {
                    $queryLote->where('producto_id', $producto->id);
                }

                $loteActivo = $queryLote
                    ->orderByRaw('fecha_vencimiento ASC NULLS LAST')
                    ->orderBy('created_at', 'ASC')
                    ->first(['id', 'porcentaje_descuento', 'presentacion_id']);

                $porcentajeDescuentoLote = (float) ($loteActivo?->porcentaje_descuento ?? 0);

                $productoArray = $producto->toArray();
                $productoArray['presentaciones'] = collect($productoArray['presentaciones'])->map(function ($pres) use ($producto, $porcentajeDescuentoLote) {

                    $descuentoAplicable = 0;
                    if ($producto->tipo_producto === 'GRANEL') {
                        $descuentoAplicable = $porcentajeDescuentoLote;
                    } else {
                        $lotePres = Lote::query()
                            ->where('presentacion_id', $pres['id'])
                            ->where('cantidad_actual', '>', 0)
                            ->where('estado', 'ACTIVO')
                            ->orderByRaw('fecha_vencimiento ASC NULLS LAST')
                            ->orderBy('created_at', 'ASC')
                            ->first(['porcentaje_descuento']);

                        $descuentoAplicable = (float) ($lotePres?->porcentaje_descuento ?? 0);
                    }

                    $precioBase = (float) $pres['precio_venta'];
                    $montoDescuento = round($precioBase * ($descuentoAplicable / 100), 2);

                    $pres['porcentaje_descuento'] = $descuentoAplicable;
                    $pres['monto_descuento_estimado'] = $montoDescuento;
                    $pres['precio_final_estimado'] = $precioBase - $montoDescuento;

                    return $pres;
                });

                return $productoArray;
            });

            return response()->json([
                'status' => 'ok',
                'data' => $productosTransformados,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error interno en el servidor',
            ], 500);
        }
    }

    public function busquedaParaCompra(Request $request)
    {

        try {
            $q = trim($request->input('q', ''));

            if (strlen($q) < 2) {
                return response()->json([]);
            }

            $productos = Producto::query()
                ->select('id', 'codigo', 'nombre', 'unidad_medida_id', 'tipo_producto')
                ->with(['unidadMedida:id,nombre,abreviatura'])
                ->where(function ($query) use ($q) {
                    $query->where('nombre', 'ilike', "%{$q}%")
                        ->orWhere('codigo', 'ilike', "%{$q}%")
                        ->orWhereHas('presentaciones.codigosBarras', function ($sub) use ($q) {
                            $sub->where('codigo', 'ilike', "%{$q}%");
                        });
                })
                ->with(['presentaciones' => function ($query) {
                    $query->where('activo', true)
                        ->select('id', 'producto_id', 'nombre', 'factor_conversion');
                }])
                ->limit(15)
                ->get();

            return response()->json([
                'status' => 'ok',
                'data' => $productos,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error interno en el servidor',
            ], 500);
        }
    }
}

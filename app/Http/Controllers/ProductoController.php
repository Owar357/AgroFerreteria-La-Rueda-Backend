<?php

namespace App\Http\Controllers;

use App\Models\Presentacion;
use App\Models\Producto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ProductoController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        try {
            if (! auth()->user()->hasRole('ADMIN|CAJERO')) {
                return response()->json([
                    'message' => 'No autorizado',
                ], 403);
            }

            $perPage = $request->input('per_page', 8);
            $page = $request->input('page', 1);

            $productos = Producto::with(['categoria:id,nombre'])
                ->select('id', 'codigo', 'nombre', 'fabricante', 'tipo_producto', 'categoria_id')
                ->orderby('id', 'desc')
                ->paginate($perPage, ['*'], 'page', $page);

            if ($productos->isEmpty()) {
                return response()->json([
                    'message' => 'No se encontraron productos',
                ], 404);
            }

            return response()->json([
                'data' => $productos->items(),
                'total' => $productos->total(),
                'per_page' => $productos->perPage(),
                'current_page' => $productos->currentPage(),
                'last_page' => $productos->lastPage(),
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al obtener productos',
            ], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {

            // Solo admins
            if (! auth()->user()->hasRole('ADMIN')) {
                return response()->json([
                    'message' => 'No autorizado',
                ], 403);
            }

            // validaciones para el reqquest
            $request->validate(
                [
                    'codigo' => 'required|string|min:2|max:14|unique:productos,codigo',
                    'nombre' => 'required|string|max:100',
                    'fabricante' => 'nullable|max:100',
                    'tipo_producto' => 'required|in:UNIDAD FIJA,GRANEL',
                    'unidad_base' => 'required',
                    'categoria_id' => 'required|exists:categorias,id',

                    'presentaciones' => 'required|array|min:1',
                    'presentaciones.*.nombre' => 'nullable|string|max:150',
                    'presentaciones.*.factor_conversion' => 'required|numeric|min:0',
                    'presentaciones.*.precio_venta' => 'required|numeric|min:0',

                    'presentaciones.*.codigos_barra' => 'required|array|min:1',
                    'presentaciones.*.codigos_barra.*.codigo' => 'required|string|unique:codigos_barras,codigo',
                ],
                [
                    'codigo.unique' => 'Ya existe un producto con este código',
                    'categoria_id.exists' => 'La categoría seleccionada no existe',
                    'presentaciones.required' => 'Debe agregar al menos una presentación',
                    'presentaciones.*.factor_conversion.required' => 'El factor de conversión es requerido',
                    'presentaciones.*.precio_venta.required' => 'El precio de venta es requerido',
                    'presentaciones.*.codigos_barra.required' => 'Debe agregar al menos un código de barra',
                ]
            );

            DB::beginTransaction();

            $producto = Producto::create([
                'codigo' => $request->codigo,
                'nombre' => $request->nombre,
                'fabricante' => $request->fabricante,
                'tipo_producto' => $request->tipo_producto,
                'unidad_base' => $request->unidad_base,
                'aplica_iva' => $request->aplica_iva,
                'categoria_id' => $request->categoria_id,
                'registrado_por' => auth()->id(),
            ]);

            foreach ($request->presentaciones as $presentacionData) {
                $presentacion = $producto->presentaciones()->create([
                    'nombre' => $presentacionData['nombre'],
                    'factor_conversion' => $presentacionData['factor_conversion'],
                    'precio_venta' => $presentacionData['precio_venta'],
                ]);

                foreach ($presentacionData['codigos_barra'] as $codigoData) {
                    $presentacion->codigosBarras()->create([
                        'codigo' => $codigoData['codigo'],
                        'activo' => true,
                    ]);
                }

            }
            DB::commit();

            return response()->json([
                'message' => 'Producto creado exitosamente',
                'data' => $producto->load(
                    'presentaciones.codigosBarras'
                ),
            ], 201);

        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Error de validación',
                'errors' => $e->errors(),
            ], 422);
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
            if (! auth()->user()->hasRole('ADMIN|CAJERO')) {
                return response()->json([
                    'status' => 'ok',
                    'message' => 'No autorizado',
                ], 403);
            }
            $productoId = Producto::where('id', $id)->exists();

            if (! $productoId) {
                return response()->json(['status' => 'ok',
                    'data' => 'El producto no existe',
                ], 404);
            }

            $presentaciones = Presentacion::select('id', 'nombre', 'factor_conversion','producto_id','precio_venta','activo')
                ->with('producto:id,unidad_base')
                ->where('producto_id', $id)
                ->withSum(['lotes as stock' => function ($query) {
                    $query->where('estado', 'ACTIVO');
                }], 'cantidad_actual')
                ->orderBy('factor_conversion','asc')
                ->get();

            if ($presentaciones->isEmpty()) {
                return response()->json(['status' => 'ok',
                    'data' => [],
                    'message' => 'No hay presentaciones registradas',
                ], 200);
            }

            return response()->json(['status' => 'ok',
                'data' => $presentaciones,
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['status' => 'ok',
                'message' => 'Error interno del servidor',
            ], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    public function buscarVenta(Request $request)
    {

       try {
         $q = trim($request->input('q', ''));

        if (strlen($q) < 2) {
            return response()->json([]);
        }

        $productos = Producto::query()
            ->select('id','codigo','nombre','unidad_base','aplica_iva')
            ->where('nombre', 'ilike', "%{$q}%")
            ->orWhere('codigo', 'ilike', "%{$q}%")
            ->orWhereHas('presentaciones.codigosBarras', function ($query) use ($q) {
                $query->where('codigo', 'ilike', "%{$q}%");
            })
            ->with(['presentaciones' => function ($query) {
                $query->where('activo', true)
                    ->select('id', 'producto_id',  'nombre','factor_conversion','precio_venta');
            }])
            ->limit(15)
            ->get();

        return response()->json([
            'status' => 'Ok',
            'data' =>  $productos
        ],200);
       } catch (\Exception $e) {
             return response()->json([
            'status' => 'Error',
            'message' =>  "Error interno en el servidor"
        ],500);
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
            ->select('id','codigo','nombre','unidad_base')
            ->where('nombre', 'ilike', "%{$q}%")
            ->orWhere('codigo', 'ilike', "%{$q}%")
            ->orWhereHas('presentaciones.codigosBarras', function ($query) use ($q) {
                $query->where('codigo', 'ilike', "%{$q}%");
            })
            ->with(['presentaciones' => function ($query) {
                $query->where('activo', true)
                    ->select('id', 'producto_id', 'nombre','factor_conversion' );
            }])
            ->limit(15)
            ->get();

        return response()->json([
            'status' => 'Ok',
            'data' =>  $productos
        ],200);
       } catch (\Exception $e) {
             return response()->json([
            'status' => 'Error',
            'message' =>  "Error interno en el servidor"
        ],500);
       }
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Producto;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ProductoController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try {
            $producto = Producto::with('registradoPor')
                ->orderby('id', 'desc')->get();

            if ($producto->isEmpty()) {
                return response()->json([
                    'message' => 'No se encontraron categorías',
                ], 404);
            }

            return response()->json($producto, 200);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al obtener las categorias',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            // validaciones para el reqquest
            $request->validate(
                [
                    'codigo' => 'required|string|min:2|max:14|unique:productos',
                    'nombre' => 'required|string|max:100',
                    'tipo_producto' => 'required',
                    'unidad_base' => 'required',
                    'categoria_id' => 'required|exists:categorias,id',
                ],
                [
                    'codigo.unique' => 'Ya existe  una categoria con este codigo',
                    'categoria_id.exists' => 'La categoría seleccionada no existe'
                ]
            );

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

             return response()->json([
                'message' => 'Producto creado exitosamente',
                'categoria' => $producto
            ], 201);



        } catch (ValidationException $e) {
            return response()->json([
            'message' => 'Error de validación',
            'errors' => $e->errors()
           ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al registrar el producto',
                'error' => $e->getMessage()
            ], 500);
        }

    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}

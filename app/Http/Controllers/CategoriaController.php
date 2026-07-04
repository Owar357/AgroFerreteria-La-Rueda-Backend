<?php

namespace App\Http\Controllers;

use App\Models\Categoria;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use App\Http\Requests\Categoria\StoreCategoriaRequest;
use App\Http\Requests\Categoria\UpdateCategoriaRequest;

class CategoriaController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        try {

            if (! auth()->user()->hasRole('ADMIN')) {
                return response()->json([
                    'message' => 'No autorizado',
                ], 403);
            }

            $perPage = $request->get('per_page', 5); // Filas por página (default 5)
            $page    = $request->get('page', 1);

            $categorias = Categoria::with('creadoPor')
                ->orderBy('id', 'desc')
                ->paginate($perPage, ['*'], 'page', $page);

            if ($categorias->isEmpty()) {
                return response()->json([
                    'message' => 'No se encontraron categorías',
                ], 404);
            }

            return response()->json([
                'data'         => $categorias->items(),
                'total'        => $categorias->total(),
                'per_page'     => $categorias->perPage(),
                'current_page' => $categorias->currentPage(),
                'last_page'    => $categorias->lastPage(),
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al obtener las categorias',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreCategoriaRequest $request)
    {
        try {

            if (! auth()->user()->hasRole('ADMIN')) {
                return response()->json([
                    'message' => 'No autorizado',
                ], 403);
            }

            $categoria = Categoria::create([
                ...$request->validated(),
                'creado_por' => auth()->id(),
            ]);

            return response()->json([
                'message'   => 'Categoria creada exitosamente',
                'categoria' => $categoria,
            ], 201);

        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Error de validación',
                'errors'  => $e->errors(),
            ], 422);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al crear la categoria',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    public function show(string $id) {}

    public function update(UpdateCategoriaRequest $request, string $id)
    {
        try {

            if (! auth()->user()->hasRole('ADMIN')) {
                return response()->json([
                    'message' => 'No autorizado',
                ], 403);
            }

            $categoria = Categoria::find($id);

            if (! $categoria) {
                return response()->json([
                    'message' => 'Categoría no encontrada',
                ], 404);
            }

            $categoria->update($request->validated());

            return response()->json([
                'message'   => 'Categoría actualizada exitosamente',
                'categoria' => $categoria->fresh(),
            ], 200);

        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Error de validación',
                'errors'  => $e->errors(),
            ], 422);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al actualizar la categoría',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    public function destroy(string $id) {}
}

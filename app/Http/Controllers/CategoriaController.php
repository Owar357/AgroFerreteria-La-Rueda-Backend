<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Categoria;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rule;

class CategoriaController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try {

            $categoria = Categoria::with('creadoPor')
            ->orderBy('id', 'desc')->get();



            if ($categoria->isEmpty()) {
                return response()->json([
                    'message' => 'No se encontraron categorías'
                ], 404);
        }

            return response()->json($categoria, 200);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al obtener las categorias',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
    //Validaciones para el request
        try{
        $request->validate(
            [
                'nombre' => 'required|string|min:2|max:50|unique:categorias'
            ],
            [
                'nombre.unique' => 'Ya existe una categoria con este nombre'
            ]
        );
        
        if (!auth()->check()) {

            return response()->json([
                'message' => 'Token vencido o no autorizado'
            ], 401);

        }

        $categoria = Categoria::create([
            'nombre' => $request->nombre,
            'creado_por' => auth()->id()
        ]);

         return response()->json([
                'message' => 'Categoria creada exitosamente',
                'categoria' => $categoria
            ], 201);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Error de validación',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al crear la categoria',
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

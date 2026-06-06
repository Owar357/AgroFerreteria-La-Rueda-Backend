<?php

namespace App\Http\Controllers;

use App\Models\CodigoBarra;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class CodigoBarraController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index() {}

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            if (! auth()->user()->hasRole('ADMIN|CAJERO')) {
                return response()->json([
                    'message' => 'No autorizado',
                ], 403);
            }

            $request->validate(
                [
                    'codigo' => 'required|string|max:50|unique:codigos_barras,codigo',
                    'presentacion_id' => 'required|exists:presentaciones,id',
                ],
                [
                    'codigo.unique' => 'El código de barra ya existe',
                    'presentacion_id.exists' => 'La presentación no existe',
                ]
            );

            $codigoBarra = CodigoBarra::create([
                'codigo' => $request->codigo,
                'activo' => true,
                'presentacion_id' => $request->presentacion_id,

            ]);

            return response()->json([
                'message' => 'Código de barra creado correctamente',
                'codigo_barra' => $codigoBarra,
            ], 201);

        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Error de validación',
                'errors' => $e->errors(),
            ], 422);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al crear código de barra',
                'error' => $e->getMessage(),
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
                    'message' => 'No autorizado',
                ], 403);
            }

            $codigosBarra = CodigoBarra::where('presentacion_id', $id)
                ->select('id', 'codigo')
                ->orderBy('id', 'desc')
                ->get();

            if ($codigosBarra->isEmpty()) {
                return response()->json([
                    'status' => 'ok',
                    'data' => [],
                    'message' => 'La presentacion no tiene codigos de barra aun',
                ], 200);
            }

            return response()->json([
                'status' => 'ok',
                'data' => $codigosBarra,
            ], 200);

        } catch (\Exception $e) {

            return response()->json([
                'message' => 'Error interno del servidor ',
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

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}

<?php

namespace App\Http\Controllers;

use App\Http\Requests\Presentaciones\StorePresentacionesRequest;
use App\Models\Presentacion;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use App\Http\Requests\Presentaciones\UpdatePresentacionesRequest;

class PresentacionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StorePresentacionesRequest $request)
    {
        try {

            $presentaciones =  Presentacion::create([
                ...$request->safe()
            ]);

            return response()->json([
                "status" => "Ok",
                "data" => $presentaciones
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                "status" => "Error",
                "message" => "Error interno en el Servidor"
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
    public function update(UpdatePresentacionesRequest $request, string $id)
    {
        try {

            if (! auth()->user()->hasRole('ADMIN')) {
                return response()->json([
                    'message' => 'No autorizado',
                ], 403);
            }

            $presentacion = Presentacion::find($id);

            if (! $presentacion) {
                return response()->json([
                    'message' => 'Presentación no encontrada.'
                ], 404);
            }
            $presentacionData = $request->validated();

            if ($request->filled('precio_venta')) {

                if ($presentacion->producto->aplica_iva == true) {
                    $presentacionData['precio_venta'] = $presentacionData['precio_venta'] * 1.13;
                }
            }

            $presentacion->update($presentacionData);

            return response()->json([
                'message' => 'Presentacion actualizada correctamente',
                'presentación' => $presentacion->fresh(),
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'message' => 'Error interno del servidor',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        try {

            $presentacion = Presentacion::findOrFail($id);

            $presentacion->activo = !$presentacion->activo;
            $presentacion->save();


            return response()->json([
                'status' => 'OK',
                'activo' =>  $presentacion->activo
            ], 200);
        } catch (ModelNotFoundException $m) {

            return response()->json([
                'status' => 'error',
                'error' => 'La presentacion no existe'
            ], 404);
        }
    }
}

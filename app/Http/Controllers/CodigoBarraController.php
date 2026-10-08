<?php

namespace App\Http\Controllers;

use App\Models\CodigoBarra;
use App\Models\Presentacion;
use App\Models\Producto;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use App\Http\Requests\CodigoBarra\StoreCodigoBarraRequest;

class CodigoBarraController extends Controller
{
    
    private function resolverPresentacionId(int|string $presentacionId): int
    {
        $presentacion = Presentacion::findOrFail($presentacionId);

        $producto = Producto::find($presentacion->producto_id);

        if ($producto && $producto->tipo_producto === 'GRANEL' && ! $presentacion->es_base) {
            $base = Presentacion::where('producto_id', $presentacion->producto_id)
                ->where('es_base', true)
                ->first();

            if ($base) {
                return $base->id;
            }
        }

        return $presentacion->id;
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreCodigoBarraRequest $request)
    {
        try {
            if (! auth()->user()->hasRole('ADMIN|CAJERO')) {
                return response()->json([
                    'message' => 'No autorizado',
                ], 403);
            }

            $datos = $request->validated();

            // Si viene de una derivada (granel), se guarda en la base
            $datos['presentacion_id'] = $this->resolverPresentacionId($datos['presentacion_id']);

            $codigoBarra = CodigoBarra::create($datos);

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

            // Si es una derivada (granel), se leen los códigos de la base
            $presentacionId = $this->resolverPresentacionId($id);

            $codigosBarra = CodigoBarra::where('presentacion_id', $presentacionId)
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
        try {
            $codigo = CodigoBarra::findOrfail($id);
            $codigo->delete();

            return response()->json([
                'status' => 'ok',
                'menssage' => ' Código eliminado correctamente'
            ], 200);
        }catch (ModelNotFoundExecption $e) {
            return response()->json([
                'status' => 'error',
                'menssage' => 'Código no encontrado'
            ], 404);
        }
    }
}
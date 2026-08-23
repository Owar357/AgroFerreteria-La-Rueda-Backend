<?php

namespace App\Http\Controllers;

use App\Http\Requests\Presentacion\StorePresentacionesRequest;
use App\Http\Requests\Presentacion\UpdatePresentacionesRequest;
use App\Models\Presentacion;
use App\Models\Producto;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

class PresentacionController extends Controller
{
    /**
     * Store a newly created resource in storage.
     */
    public function store(StorePresentacionesRequest $request)
    {
        try {

            DB::beginTransaction();

            $data = $request->validated();

            $producto = Producto::find($data['producto_id']);

            if ($producto->aplica_iva && $request->filled('precio_venta')) {
                $data['precio_venta'] = (float) $data['precio_venta'] * 1.13;
            }

            $presentacion = Presentacion::create($data);

            DB::commit();

            return response()->json([
                'status' => 'ok',
                'message' => 'Presentación creada exitosamente',
                'data' => $presentacion,
            ], 201);
        } catch (\Throwable $th) {
            DB::rollBack();

            return response()->json([
                'status' => 'error',
                'message' => 'Error interno en el Servidor',
            ], 500);
        }
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
                    'status' => 'error',
                    'message' => 'Presentación no encontrada.',
                ], 404);
            }

            DB::beginTransaction();

            $presentacionData = $request->validated();

            if ($request->filled('precio_venta')) {

                if ($presentacion->producto->aplica_iva == true) {
                    $presentacionData['precio_venta'] = $presentacionData['precio_venta'] * 1.13;
                }
            }

            if ($request->filled('factor_conversion')) {
                if ($presentacion->es_base && (float) $presentacionData['factor_conversion'] !== 1.0) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'La presentación base debe tener factor de conversión mayor a 0',
                    ], 422);
                }
            }

            if ($request->filled('stock_minimo')) {
                $producto = $presentacion->producto;
                if ($producto->tipo_producto === 'GRANEL' && ! $presentacion->es_base) {
                    if ((float) $presentacionData['stock_minimo'] > 0) {
                        DB::rollBack();

                        return response()->json([
                            'status' => 'error',
                            'message' => 'El stock mínimo solo puede asignarse a la presentación base. Las presentaciones derivadas deben tener stock_minimo = 0.',
                        ], 422);
                    }
                }
            }

            $presentacion->update($presentacionData);

            DB::commit();

            return response()->json([
                'status' => 'ok',
                'message' => 'Presentacion actualizada correctamente',
                'data' => $presentacion->fresh(['producto', 'unidadMedida']),
            ], 200);

        } catch (Exception $e) {

            DB::rollBack();

            return response()->json([
                'status' => 'error',
                'message' => 'Error interno del servidor',
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

            if ($presentacion->lotes()->where('estado', 'ACTIVO')->exists()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'No se puede desactivar la presentación porque tiene lotes activos con stock disponible.',
                ], 422);
            }

            $presentacion->activo = ! $presentacion->activo;
            $presentacion->save();

            return response()->json([
                'status' => 'ok',
                'message' => $presentacion->activo ? 'Presentación activada correctamente.' : 'Presentación desactivada correctamente.',
                'activo' => $presentacion->activo,
            ], 200);
            
        } catch (ModelNotFoundException $m) {

            return response()->json([
                'status' => 'error',
                'error' => 'La presentacion no existe',
            ], 404);
        }
    }
}

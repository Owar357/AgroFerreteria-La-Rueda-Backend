<?php

namespace App\Http\Controllers;

use App\Http\Requests\Lote\updateLoteDescuentoRequest;
use App\Models\Lote;
use App\Models\Presentacion;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;

class LoteController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        try {
            if (! auth()->user()->hasAnyRole(['ADMIN', 'CAJERO'])) {
                return response()->json([
                    'message' => 'No autorizado',
                ], 403);
            }

            $presentacion = Presentacion::with('producto')->find($request->presentacion_id);

            if (! $presentacion) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Presentación inexistente',
                ], 404);
            }

            $perPage = $request->get('per_page', 5);
            $page = $request->get('page', 1);

            $consultarLotes = Lote::query();

            if ($presentacion->producto?->tipo_producto === 'GRANEL') {
                $consultarLotes->where('producto_id', $presentacion->producto_id);
            } else {
                $consultarLotes->where('presentacion_id', $presentacion->id);
            }

            $lotes = $consultarLotes
                ->select(
                    'id',
                    'lote_interno',
                    'lote_fabricante',
                    'fecha_vencimiento',
                    'cantidad_inicial',
                    'cantidad_actual',
                    'costo_unitario_compra',
                    'estado',
                    'porcentaje_descuento'
                )
                ->orderByRaw('fecha_vencimiento ASC NULLS LAST')
                ->paginate($perPage, ['*'], 'page', $page);

            return response()->json([
                'status' => 'ok',
                'data' => $lotes->items(),
                'total' => $lotes->total(),
                'per_page' => $lotes->perPage(),
                'current_page' => $lotes->currentPage(),
                'last_page' => $lotes->lastPage(),
            ]);

        } catch (\Exception $e) {

            return response()->json([
                'status' => 'error',
                'message' => 'Error servidor',
            ], 500);
        }
    }

    public function actualizarDescuento(updateLoteDescuentoRequest $request, $id)
    {
        try {
            $lote = Lote::findOrFail($id);

            if ($lote->estado !== 'ACTIVO') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Solo se puede asignar descuento a lotes en estado ACTIVO.',
                ], 422);
            }

            if ((float) $lote->cantidad_actual <= 0) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'No se puede asignar descuento a un lote sin stock disponible.',
                ], 422);
            }

            $valorInput = $request->validated()['porcentaje_descuento'] ?? $request->input('porcentaje_descuento');

            $lote->porcentaje_descuento = ($valorInput !== null && (float) $valorInput > 0)
                ? (float) $valorInput
                : null;

            $lote->save();

            return response()->json([
                'status' => 'ok',
                'message' => 'Porcentaje de descuento actualizado correctamente.',
                'data' => [
                    'lote_id' => $lote->id,
                    'lote_interno' => $lote->lote_interno,
                    'porcentaje_descuento' => $lote->porcentaje_descuento,
                ],
            ], 200);

        } catch (ModelNotFoundException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'El lote especificado no existe.',
            ], 404);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error al actualizar el descuento:',
            ], 500);
        }
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Alerta;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class AlertasController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try {
            $alertas = Alerta::where('estado', 'ACTIVA')
                ->with(['producto', 'lote', 'compra'])
                ->orderByRaw("CASE WHEN prioridad = 'ALTA' THEN 1 WHEN prioridad = 'MEDIA' THEN 2 ELSE 3 END")
                ->orderBy('created_at', 'desc')
                ->get();

            $noLeidas = $alertas->where('leida', false)->count();

            return response()->json([
                'status' => 'ok',
                'data' => $alertas,
                'no_leidas' => $noLeidas,
            ], 200);

        } catch (\Throwable $th) {

            \Log::error('Error al obtener alertas: '.$th->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Error interno del servidor'.$th->getMessage(),
            ], 500);
        }
    }

    public function marcarLeida(int $id)
    {
        try {
            $alerta = Alerta::where('estado', 'ACTIVA')->findOrFail($id);

            $alerta->leida = ! $alerta->leida;
            $alerta->leida_por = $alerta->leida ? auth()->id() : null;
            $alerta->save();

            return response()->json([
                'status' => 'ok',
                'data' => $alerta,
                'message' => $alerta->leida ? 'Alerta marcada como leída' : 'Alerta marcada como no leída',
            ], 200);

        } catch (ModelNotFoundException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Alerta no encontrada',
            ], 404);
        } catch (\Throwable $th) {

            return response()->json([
                'status' => 'error',
                'message' => 'Error interno del servidor',
            ], 500);
        }
    }
}

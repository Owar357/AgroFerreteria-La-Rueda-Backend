<?php

namespace App\Http\Controllers;

use App\Http\Requests\Caja\MovimientoExternoRequest;
use App\Models\AperturaVenta;
use App\Models\MovimientoExternoCaja;
use App\Services\AutenticacionAdminService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;

class MovimientoExternoCajaController extends Controller
{
    public function __construct(private AutenticacionAdminService $authAdmin) {}

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $fechaDesde = $request->fecha_desde ?? Carbon::now()->toDateString();
        $fechaHasta = $request->fecha_hasta ?? Carbon::now()->toDateString();
        $perPage = $request->per_page ?? 7;

        $query = MovimientoExternoCaja::whereBetween('created_at', [
            $fechaDesde.' 00:00:00',
            $fechaHasta.' 23:59:59']);  

        if ($request->filled('tipo_movimiento')) {
            $query->where('tipo_movimiento', $request->tipo_movimiento);
        }

         $movimientos = $query->with('user:id,name')
        ->orderBy('created_at', 'desc')
        ->paginate($perPage);

        return response()->json($movimientos, 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(MovimientoExternoRequest $request)
    {
        try {

            $aperturaVenta = AperturaVenta::where('cajero_id', auth()->id())
                ->where('estado', 'ABIERTA')
                ->firstOrFail();

            MovimientoExternoCaja::create([
                ...$request->validated(),
                'user_id' => auth()->id(),
                'apertura_venta_id' => $aperturaVenta->id,
            ]);

            return response()->json([
                'status' => 'ok',
                'message' => 'El movimiento externo de caja fue añadido correctamente',
            ], 200);
        } catch (ModelNotFoundException $m) {
            return response()->json([
                'status' => 'error',
                'message' => 'Actualmente no existe una apertura  de venta activa para registrar un movimiento',
            ], 404);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error interno del servidor',
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

    public function anularMovimiento(Request $request, MovimientoExternoCaja $movimiento)
    {
        try {
            $resultado = $this->authAdmin->verificarCredencial($request->email, $request->password);

            if ($resultado['error']) {
                return response()->json([
                    'status' => 'error',
                    'message' => $resultado['message'],
                ], $resultado['code']);
            }

            $admin = $resultado['usuario'];

            if ($movimiento->es_anulado) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Este movimiento ya fue anulado',
                ], 422);
            }

            $movimiento->update([
                'es_anulado' => true,
                'anulado_por' => $admin->id,
                'anulado_at' => now(),
            ]);
            
            return response()->json([
                'status' => 'ok',
                'message' => 'Movimiento anulado correctamente',
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error interno del servidor' ,
            ], 500);
        }
    }
}

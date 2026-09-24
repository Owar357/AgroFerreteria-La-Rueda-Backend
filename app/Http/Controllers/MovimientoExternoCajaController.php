<?php

namespace App\Http\Controllers;

use App\Http\Requests\Caja\AnularMovimientoRequest;
use App\Http\Requests\Caja\MovimientoExternoRequest;
use App\Models\AperturaVenta;
use App\Models\MovimientoExternoCaja;
use App\Services\AutenticacionAdminService;
use App\Services\CajaService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;


class MovimientoExternoCajaController extends Controller
{
    public function __construct(
        private AutenticacionAdminService $authAdmin,
        private CajaService $cajaService,
    ) {}

    
    public function index(Request $request)
    {
        try {
            $perPage = min(max((int) $request->input('per_page', 7), 1), 100);

            $query = MovimientoExternoCaja::query();

            if ($request->boolean('turno_actual')) {
                $turno = $this->cajaService->turnoActivo();

                if ($turno) {
                    $query->where('apertura_venta_id', $turno->id);
                } else {
                    $query->whereRaw('1 = 0');
                }
            } else {
                $desde = $request->filled('fecha_desde') ? $request->fecha_desde : now()->toDateString();
                $hasta = $request->filled('fecha_hasta') ? $request->fecha_hasta : now()->toDateString();

                $query->whereBetween('created_at', [$desde.' 00:00:00', $hasta.' 23:59:59']);
            }

            if ($request->filled('tipo_movimiento')) {
                $query->where('tipo_movimiento', $request->tipo_movimiento);
            }

            $sumas = (clone $query)
                ->where('es_anulado', false)
                ->selectRaw("
                    COALESCE(SUM(CASE WHEN tipo_movimiento = 'ENTRADA' THEN monto ELSE 0 END), 0) AS entradas,
                    COALESCE(SUM(CASE WHEN tipo_movimiento = 'SALIDA' THEN monto ELSE 0 END), 0) AS salidas
                ")
                ->first();

            $entradas = bcadd((string) $sumas->entradas, '0', 2);
            $salidas = bcadd((string) $sumas->salidas, '0', 2);

            $movimientos = $query
                ->with('user:id,name')
                ->orderByDesc('created_at')
                ->orderByDesc('id')
                ->paginate($perPage);

            return response()->json([
                ...$movimientos->toArray(),
                'totales' => [
                    'entradas' => $entradas,
                    'salidas' => $salidas,
                    'balance' => bcsub($entradas, $salidas, 2),
                ],
            ], 200);

        } catch (\Throwable $e) {
            return $this->falloInterno($e, 'index');
        }
    }

    
    public function store(MovimientoExternoRequest $request)
    {
        try {
            $resultado = DB::transaction(function () use ($request) {
                $turno = $this->cajaService->turnoActivo(true);

                if (! $turno) {
                    throw new \DomainException('Actualmente no existe una apertura de venta activa para registrar un movimiento.', 404);
                }

                if ((int) $turno->cajero_id !== (int) auth()->id()) {
                    throw new \DomainException('El turno activo pertenece a otro cajero.', 403);
                }

                $monto = $this->cajaService->monto($request->monto);

                if ($request->tipo_movimiento === 'SALIDA') {
                    $retirable = $this->cajaService->saldoRetirable($turno);

                    if (bccomp($monto, $retirable, 2) > 0) {
                        $fondo = $turno->fondo_fijo_referencia ?? $this->cajaService->fondoFijo();

                        throw new \DomainException(sprintf(
                            'La salida ($%s) supera el saldo disponible para retiro ($%s) y dejaría la caja por debajo del fondo fijo protegido ($%s). '.
                            'Si el dinero se necesita de todos modos, registre primero una ENTRADA con la diferencia que falta.',
                            $monto,
                            $retirable,
                            $fondo
                        ), 422);
                    }
                }

                $movimiento = MovimientoExternoCaja::create([
                    'tipo_movimiento' => $request->tipo_movimiento,
                    'origen' => 'VENTAS',
                    'monto' => $monto,
                    'motivo' => $request->motivo,
                    'apertura_venta_id' => $turno->id,
                    'user_id' => auth()->id(),
                ]);

                return [
                    'id' => $movimiento->id,
                    'efectivo_disponible' => $this->cajaService->efectivoDisponible($turno),
                    'saldo_retirable' => $this->cajaService->saldoRetirable($turno),
                ];
            });

            return response()->json([
                'status' => 'ok',
                'message' => 'El movimiento externo de caja fue añadido correctamente',
                'id' => $resultado['id'],
                'efectivo_disponible' => $resultado['efectivo_disponible'],
                'saldo_retirable' => $resultado['saldo_retirable'],
            ], 200);

        } catch (\DomainException $e) {
            return $this->respuestaError($e->getMessage(), $this->statusDe($e));
        } catch (\Throwable $e) {
            return $this->falloInterno($e, 'store');
        }
    }

    public function show(string $id)
    {
        //
    }

    
    public function anularMovimiento(AnularMovimientoRequest $request, MovimientoExternoCaja $movimiento)
    {
        try {
            $resultado = $this->authAdmin->verificarCredencial($request->email, $request->password);

            if ($resultado['error']) {
                return $this->respuestaError($resultado['message'], $resultado['code']);
            }

            $admin = $resultado['usuario'];

            DB::transaction(function () use ($movimiento, $admin) {
                // Se bloquea primero el turno (mismo orden que store) y luego se relee el movimiento.
                $turno = AperturaVenta::whereKey($movimiento->apertura_venta_id)->lockForUpdate()->firstOrFail();
                $movimiento->refresh();

                if ($movimiento->es_anulado) {
                    throw new \DomainException('Este movimiento ya fue anulado', 422);
                }

                if ($turno->estado !== 'ABIERTA') {
                    throw new \DomainException('No se puede anular un movimiento de un turno que ya fue cerrado.', 422);
                }

                if ($movimiento->tipo_movimiento === 'ENTRADA'
                    && bccomp($movimiento->monto, $this->cajaService->saldoRetirable($turno), 2) > 0) {
                    throw new \DomainException(
                        sprintf(
                            'No se puede anular esta entrada: el efectivo quedaría por debajo del fondo fijo protegido (saldo retirable actual: $%s).',
                            $this->cajaService->saldoRetirable($turno)
                        ),
                        422
                    );
                }

                $movimiento->update([
                    'es_anulado' => true,
                    'anulado_por' => $admin->id,
                    'anulado_at' => now(),
                ]);
            });

            return response()->json([
                'status' => 'ok',
                'message' => 'Movimiento anulado correctamente',
            ], 200);

        } catch (\DomainException $e) {
            return $this->respuestaError($e->getMessage(), $this->statusDe($e));
        } catch (ModelNotFoundException) {
            return $this->respuestaError('No se encontró el turno del movimiento.', 404);
        } catch (\Throwable $e) {
            return $this->falloInterno($e, 'anularMovimiento');
        }
    }

    
    private function statusDe(\DomainException $e): int
    {
        $codigo = (int) $e->getCode();

        return ($codigo >= 400 && $codigo <= 599) ? $codigo : 422;
    }

    private function respuestaError(string $mensaje, int $status = 422): JsonResponse
    {
        return response()->json([
            'status' => 'error',
            'message' => $mensaje,
        ], $status);
    }

    private function falloInterno(\Throwable $e, string $contexto): JsonResponse
    {
        Log::error("MovimientoExternoCajaController::{$contexto}: ".$e->getMessage(), [
            'exception' => $e,
            'usuario_id' => auth()->id(),
        ]);

        return $this->respuestaError('Error interno del servidor', 500);
    }
}
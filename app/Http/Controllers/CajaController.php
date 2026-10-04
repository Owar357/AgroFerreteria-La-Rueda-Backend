<?php

namespace App\Http\Controllers;

use App\Http\Requests\Caja\AbrirAperturaCajaRequest;
use App\Http\Requests\Caja\AbrirAperturaVentaRequest;
use App\Http\Requests\Caja\CerrarVentaCajaRequest;
use App\Http\Requests\Caja\CuadrarVentaRequest;
use App\Models\AperturaCaja;
use App\Models\AperturaVenta;
use App\Services\AutenticacionAdminService;
use App\Services\CajaService;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;


class CajaController extends Controller
{
    private const TOKEN_TTL_MINUTOS = 10;

    public function __construct(
        private CajaService $cajaService,
        private AutenticacionAdminService $authAdmin,
    ) {}

    // ------------------------------------------------------------------
    // Caja general
    // ------------------------------------------------------------------

    public function abrirCaja(AbrirAperturaCajaRequest $request)
    {
        try {
            $resultado = $this->authAdmin->verificarCredencial($request->email, $request->password);

            if ($resultado['error']) {
                return $this->respuestaError($resultado['message'], $resultado['code']);
            }

            if (AperturaCaja::where('estado', 'ABIERTO')->exists()) {
                return $this->respuestaError('Ya existe una apertura de caja activa.', 422);
            }

            AperturaCaja::create([
                'fecha_hora_apertura' => now(),
                'estado' => 'ABIERTO',
                'abierta_por' => $resultado['usuario']->id,
            ]);

            return response()->json([
                'status' => 'ok',
                'message' => 'Caja abierta correctamente',
            ], 200);

        } catch (UniqueConstraintViolationException) {
            return $this->respuestaError('Ya existe una apertura de caja activa.', 422);
        } catch (\Throwable $e) {
            return $this->falloInterno($e, 'abrirCaja');
        }
    }

    public function estadoCaja()
    {
        try {
            $aperturaCaja = AperturaCaja::where('estado', 'ABIERTO')->first();
            $turno = $this->cajaService->turnoActivo();
            $turno?->loadMissing('cajero:id,name');

            $esMiTurno = $turno && (int) $turno->cajero_id === (int) auth()->id();
            $puedeVerMonto = $esMiTurno || $this->esAdmin();

            return response()->json([
                'caja_abierta' => (bool) $aperturaCaja,
                'venta_abierta' => (bool) $esMiTurno,
                'monto_inicial' => ($turno && $puedeVerMonto) ? $turno->monto_inicial : 0,
                'fondo_fijo' => $this->cajaService->fondoFijo(),
                'turno_activo' => $turno ? [
                    'id' => $turno->id,
                    'cajero_id' => $turno->cajero_id,
                    'cajero_nombre' => $turno->cajero->name,
                    'es_mio' => $esMiTurno,
                    'fecha_hora_apertura' => $turno->fecha_hora_apertura?->toIso8601String(),
                ] : null,
            ], 200);

        } catch (\Throwable $e) {
            return $this->falloInterno($e, 'estadoCaja');
        }
    }

    // ------------------------------------------------------------------
    // Apertura de venta (turno)
    // ------------------------------------------------------------------

    public function abrirVenta(AbrirAperturaVentaRequest $request)
    {
        try {
            [$montoContado, $denominaciones] = $this->resolverConteo($request, 'monto_inicial');

            if (bccomp($montoContado, '0', 2) <= 0) {
                return $this->respuestaError('Debe registrar el dinero con el que inicia el turno (mayor a $0.00).', 422);
            }

            $evaluacion = $this->cajaService->evaluarApertura($montoContado);
            $justificacion = trim((string) $request->input('justificacion_apertura', ''));

            if ($evaluacion['requiere_justificacion'] && $justificacion === '') {
                return $this->respuestaError(
                    sprintf(
                        'El monto contado ($%s) es distinto del fondo fijo ($%s). Debe ingresar la justificación de apertura.',
                        $evaluacion['monto_contado'],
                        $evaluacion['fondo_fijo']
                    ),
                    422,
                    [
                        'requiere_justificacion' => true,
                        'fondo_fijo' => $evaluacion['fondo_fijo'],
                        'monto_contado' => $evaluacion['monto_contado'],
                        'tipo' => $evaluacion['tipo'],
                    ]
                );
            }

            $turno = DB::transaction(function () use ($evaluacion, $justificacion, $denominaciones) {
                $aperturaCaja = AperturaCaja::where('estado', 'ABIERTO')->lockForUpdate()->first();

                if (! $aperturaCaja) {
                    throw new \DomainException('No se puede aperturar la venta, no hay apertura de caja disponible.', 422);
                }

                $activo = $this->cajaService->turnoActivo(true);

                if ($activo) {
                    $activo->loadMissing('cajero:id,name');

                    throw new \DomainException(
                        (int) $activo->cajero_id === (int) auth()->id()
                            ? 'Ya tiene una apertura de venta activa.'
                            : "Ya existe un turno abierto por {$activo->cajero->name}.",
                        422
                    );
                }

                return AperturaVenta::create([
                    'fecha_hora_apertura' => now(),
                    'monto_inicial' => $evaluacion['monto_contado'],
                    'fondo_fijo_referencia' => $evaluacion['fondo_fijo'],
                    'justificacion_apertura' => $evaluacion['requiere_justificacion'] ? $justificacion : null,
                    'denominaciones_apertura' => $denominaciones,
                    'estado' => 'ABIERTA',
                    'apertura_caja_id' => $aperturaCaja->id,
                    'cajero_id' => auth()->id(),
                ]);
            });

            return response()->json([
                'status' => 'ok',
                'message' => 'Venta aperturada correctamente',
                'apertura_venta_id' => $turno->id,
                'monto_inicial' => $evaluacion['monto_contado'],
                'fondo_fijo' => $evaluacion['fondo_fijo'],
            ], 200);

        } catch (\DomainException $e) {
            return $this->respuestaError($e->getMessage(), $this->statusDe($e));
        } catch (UniqueConstraintViolationException) {
            return $this->respuestaError('Ya existe un turno abierto. No se puede abrir otro.', 422);
        } catch (\Throwable $e) {
            return $this->falloInterno($e, 'abrirVenta');
        }
    }

    // ------------------------------------------------------------------
    // Cuadre y cierre
    // ------------------------------------------------------------------

    public function cuadrarVenta(CuadrarVentaRequest $request)
    {
        try {
            $turno = $this->turnoDelUsuario();

            $resultado = $this->authAdmin->verificarCredencial($request->email, $request->password);

            if ($resultado['error']) {
                return $this->respuestaError($resultado['message'], $resultado['code']);
            }

            [$montoContado, $denominaciones] = $this->resolverConteo($request, 'monto_contado');

            $cierre = $this->cajaService->calcularCierre($turno, $montoContado);

            // El token queda ligado al monto contado y al esperado calculados aquí:
            // el cierre no acepta un monto distinto ni un resumen que haya cambiado.
            $token = Str::random(40);

            Cache::put($this->claveToken($token), [
                'admin_id' => $resultado['usuario']->id,
                'apertura_venta_id' => $turno->id,
                'monto_contado' => $cierre['monto_contado'],
                'monto_esperado' => $cierre['monto_esperado'],
                'denominaciones' => $denominaciones,
            ], now()->addMinutes(self::TOKEN_TTL_MINUTOS));

            $turno->loadMissing('cajero:id,name');

            return response()->json([
                'status' => 'ok',
                'nombre_cajero' => $turno->cajero->name,
                'numero_caja' => '001',
                'fecha_hora' => $turno->fecha_hora_apertura->format('d/m/y H:i:s'),
                'fondo_fijo' => $cierre['fondo_fijo'],
                'monto_inicial' => $cierre['monto_inicial'],
                'total_entradas' => $cierre['total_entradas'],
                'total_salidas' => $cierre['total_salidas'],
                'total_ventas_efectivo' => $cierre['ventas_efectivo'],
                'total_ventas_tarjeta' => $cierre['ventas_tarjeta'],
                'total_ventas_transferencia' => $cierre['ventas_transferencia'],
                'token_autorizacion' => $token,
                'monto_esperado' => $cierre['monto_esperado'],
                'monto_contado' => $cierre['monto_contado'],
                'diferencia' => $cierre['diferencia'],
                'tipo_diferencia' => $cierre['tipo_diferencia'],
                'escenario' => $cierre['escenario'],
                'retiro_efectivo' => $cierre['retiro_efectivo'],
                'fondo_siguiente_turno' => $cierre['fondo_siguiente_turno'],
            ], 200);

        } catch (\DomainException $e) {
            return $this->respuestaError($e->getMessage(), $this->statusDe($e));
        } catch (\Throwable $e) {
            return $this->falloInterno($e, 'cuadrarVenta');
        }
    }

    public function cerrarVentaCaja(CerrarVentaCajaRequest $request)
    {
        try {
            $clave = $this->claveToken($request->token_autorizacion);
            $datos = Cache::get($clave);

            if (! $datos) {
                return $this->respuestaError('Token de autorización inválido o expirado. Vuelva a realizar el cuadre.', 422, ['token_invalido' => true]);
            }

            $justificacion = trim((string) $request->input('justificacion', ''));

            $cierre = DB::transaction(function () use ($datos, $justificacion) {
                $turno = $this->turnoDelUsuario(true);

                if ((int) $datos['apertura_venta_id'] !== (int) $turno->id) {
                    throw new \DomainException('El token de autorización no corresponde al turno activo.', 422);
                }

                // Se recalcula con el estado actual del turno, usando SOLO el monto ligado al token.
                $cierre = $this->cajaService->calcularCierre($turno, $datos['monto_contado']);

                if (bccomp($cierre['monto_esperado'], $datos['monto_esperado'], 2) !== 0) {
                    throw new \DomainException(
                        'Se registraron ventas o movimientos después del cuadre. Repita el cuadre para continuar.',
                        409
                    );
                }

                $hayDiferencia = $cierre['tipo_diferencia'] !== 'CUADRADO';

                if ($hayDiferencia && $justificacion === '') {
                    throw new \DomainException('La justificación es obligatoria cuando hay diferencia de caja.', 422);
                }

                $turno->update([
                    'fecha_hora_cierre' => now(),
                    'monto_esperado' => $cierre['monto_esperado'],
                    'monto_contado' => $cierre['monto_contado'],
                    'diferencia' => $cierre['diferencia'],
                    'estado_arqueo' => $cierre['tipo_diferencia'],
                    'justificacion' => $hayDiferencia ? $justificacion : null,
                    'retiro_efectivo' => $cierre['retiro_efectivo'],
                    'fondo_siguiente_turno' => $cierre['fondo_siguiente_turno'],
                    'denominaciones_cierre' => $datos['denominaciones'] ?? null,
                    'estado' => 'CERRADA',
                    'cerrada_por' => $datos['admin_id'],
                ]);

                AperturaCaja::findOrFail($turno->apertura_caja_id)->update([
                    'fecha_hora_cierre' => now(),
                    'estado' => 'CERRADO',
                    'cerrada_por' => $datos['admin_id'],
                ]);

                return $cierre;
            });

            Cache::forget($clave);

            return response()->json([
                'status' => 'ok',
                'message' => 'Caja cerrada correctamente',
                'escenario' => $cierre['escenario'],
                'diferencia' => $cierre['diferencia'],
                'tipo_diferencia' => $cierre['tipo_diferencia'],
                'retiro_efectivo' => $cierre['retiro_efectivo'],
                'fondo_siguiente_turno' => $cierre['fondo_siguiente_turno'],
            ], 200);

        } catch (\DomainException $e) {
            return $this->respuestaError($e->getMessage(), $this->statusDe($e));
        } catch (\Throwable $e) {
            return $this->falloInterno($e, 'cerrarVentaCaja');
        }
    }

    // ------------------------------------------------------------------
    // Resumen del turno activo
    // ------------------------------------------------------------------

    public function resumenTurno()
    {
        try {
            $turno = $this->cajaService->turnoActivo();

            $vacio = [
                'status' => 'ok',
                'monto_inicial' => '0.00',
                'ventas_contado' => '0.00',
                'ventas_tarjeta' => '0.00',
                'ventas_transferencia' => '0.00',
                'total_entradas' => '0.00',
                'total_salidas' => '0.00',
                'monto_en_caja' => '0.00',
                'monto_esperado' => '0.00',
                'total_en_caja' => '0.00',
                'apertura_venta_id' => null,
                'es_mi_turno' => false,
            ];

            if (! $turno) {
                return response()->json($vacio, 200);
            }

            $esMiTurno = (int) $turno->cajero_id === (int) auth()->id();

            // Solo el dueño del turno o un admin ven las cifras del turno activo.
            if (! $esMiTurno && ! $this->esAdmin()) {
                return response()->json($vacio, 200);
            }

            $resumen = $this->cajaService->resumen($turno);

            $totalEnCaja = bcsub(
                bcadd($resumen['total_ventas'], $resumen['total_entradas'], 2),
                $resumen['total_salidas'],
                2
            );

            return response()->json([
                'status' => 'ok',
                'monto_inicial' => $resumen['monto_inicial'],
                'ventas_contado' => $resumen['ventas_efectivo'],
                'ventas_tarjeta' => $resumen['ventas_tarjeta'],
                'ventas_transferencia' => $resumen['ventas_transferencia'],
                'total_entradas' => $resumen['total_entradas'],
                'total_salidas' => $resumen['total_salidas'],
                'monto_en_caja' => $resumen['efectivo_disponible'],
                'monto_esperado' => $resumen['efectivo_disponible'], // igual mientras el turno sigue abierto
                'total_en_caja' => $totalEnCaja,
                'apertura_venta_id' => $turno->id,
                'es_mi_turno' => $esMiTurno,
            ], 200);

        } catch (\Throwable $e) {
            return $this->falloInterno($e, 'resumenTurno');
        }
    }

    // ------------------------------------------------------------------
    // Helpers privados
    // ------------------------------------------------------------------

    /**
     * Turno activo, validando que pertenezca al usuario autenticado.
     *
     * @throws \DomainException 404 si no hay turno, 403 si es de otro cajero
     */
    private function turnoDelUsuario(bool $bloquear = false): AperturaVenta
    {
        $turno = $this->cajaService->turnoActivo($bloquear);

        if (! $turno) {
            throw new \DomainException('No tiene una apertura de venta activa.', 404);
        }

        if ((int) $turno->cajero_id !== (int) auth()->id()) {
            throw new \DomainException('El turno activo pertenece a otro cajero.', 403);
        }

        return $turno;
    }

    /**
     * Total contado y desglose normalizado.
     * Si viene 'denominaciones', el total lo calcula el servidor; si no (TRANSICIÓN), usa el monto enviado.
     *
     * @return array{0: string, 1: array<string,int>|null}
     */
    private function resolverConteo(Request $request, string $campoMontoLegacy): array
    {
        if ($request->has('denominaciones')) {
            $desglose = $this->cajaService->normalizarDenominaciones((array) $request->input('denominaciones'));

            return [$this->cajaService->totalDenominaciones($desglose), $desglose];
        }

        return [$this->cajaService->monto($request->input($campoMontoLegacy)), null];
    }

    private function esAdmin(): bool
    {
        return (bool) auth()->user()?->hasRole('ADMIN');
    }

    private function claveToken(string $token): string
    {
        return "autorizacion_cierre_{$token}";
    }

    private function statusDe(\DomainException $e): int
    {
        $codigo = (int) $e->getCode();

        return ($codigo >= 400 && $codigo <= 599) ? $codigo : 422;
    }

    private function respuestaError(string $mensaje, int $status = 422, array $extra = []): JsonResponse
    {
        return response()->json([
            'status' => 'error',
            'message' => $mensaje,
            ...$extra,
        ], $status);
    }

    private function falloInterno(\Throwable $e, string $contexto): JsonResponse
    {
        Log::error("CajaController::{$contexto}: ".$e->getMessage(), [
            'exception' => $e,
            'usuario_id' => auth()->id(),
        ]);

        return $this->respuestaError('Error interno del servidor', 500);
    }
}
<?php

namespace App\Http\Controllers;

use App\Http\Requests\Caja\AbrirAperturaCajaRequest;
use App\Http\Requests\Caja\AbrirAperturaVentaRequest;
use App\Models\AperturaCaja;
use App\Models\AperturaVenta;
use App\Models\MovimientoExternoCaja;
use App\Models\User;
use App\Models\Venta;
use Hash;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CajaController extends Controller
{
    private function verificarCredenciales(string $email, string $password)
    {
        $usuario = User::where('email', $email)->first();

        if (! $usuario || ! Hash::check($password, $usuario->password)) {
            return ['error' => true, 'message' => 'Credenciales inválidas', 'code' => 401];
        }

        if (! $usuario->hasRole('ADMIN')) {
            return ['error' => true, 'message' => 'No tienes permisos para realizar esta acción', 'code' => 403];
        }

        return ['error' => false, 'usuario' => $usuario];
    }

    public function abrirCaja(AbrirAperturaCajaRequest $request)
    {
        try {

            $resultado = $this->verificarCredenciales($request->email, $request->password);

            if ($resultado['error']) {
                return response()->json([
                    'status' => 'error',
                    'message' => $resultado['message'],
                ], $resultado['code']);
            }

            $usuario = $resultado['usuario'];

            $yaHayCajaAbierta = AperturaCaja::where('estado', 'ABIERTO')->exists();

            if ($yaHayCajaAbierta) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Ya existe una apertura de caja activa ',
                ], 422);
            }

            AperturaCaja::create([
                'fecha_hora_apertura' => now(),
                'estado' => 'ABIERTO',
                'abierta_por' => $usuario->id,
            ]);

            return response()->json([
                'status' => 'ok',
                'message' => 'Caja abierta correctamente',
            ], 200);

        } catch (\Exception $e) {

            return response()->json([
                'status' => 'error',
                'message' => 'Error interno del servidor',
            ], 500);

        }

    }

    public function abrirVenta(AbrirAperturaVentaRequest $request)
    {
        try {

            $hayAperturaCaja = AperturaCaja::where('estado', 'ABIERTO')->first();

            if (! $hayAperturaCaja) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'No se puede aperturar la venta, no hay apertura de caja disponible',
                ], 422);
            }

            $yaTieneAperturaVenta = AperturaVenta::where('cajero_id', auth()->id())
                ->where('estado', 'ABIERTA')
                ->exists();

            if ($yaTieneAperturaVenta) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Ya tiene un apertura de venta activa',
                ], 422);
            }

            $abrirVenta = AperturaVenta::create([
                'monto_inicial' => $request->monto_inicial,
                'estado' => 'ABIERTA',
                'apertura_caja_id' => $hayAperturaCaja->id,
                'cajero_id' => auth()->id(),
                'fecha_hora_apertura' => now(),
            ]);

            Venta::whereNull('apertura_venta_id')
                ->where('vendido_por', auth()->id())
                ->update(['apertura_venta_id' => $abrirVenta->id]);

            return response()->json([
                'status' => 'ok',
                'message' => 'Venta aperturada correctamente',
            ], 200);

        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error interno del servidor',
            ], 500);
        }
    }

    public function cuadrarVenta(Request $request)
    {
        try {
            $resultado = $this->verificarCredenciales($request->email, $request->password);

            if ($resultado['error']) {
                return response()->json([
                    'status' => 'error',
                    'message' => $resultado['message'],
                ], $resultado['code']);
            }

            $admin = $resultado['usuario'];

            $aperturaVenta = AperturaVenta::where('cajero_id', auth()->id())
                ->where('estado', 'ABIERTA')
                ->with('cajero:id,name')
                ->firstOrFail();

            $totalVentaEfectivo = Venta::where('apertura_venta_id', $aperturaVenta->id)
                ->where('tipo_pago', 'EFECTIVO')
                ->selectRaw('COALESCE(SUM(efectivo_recibido - cambio), 0) as total')
                ->value('total');

            $totalVentaTransferencia = Venta::where('apertura_venta_id', $aperturaVenta->id)
                ->where('tipo_pago', 'TRANSFERENCIA')
                ->sum('total');

            $movimientos = MovimientoExternoCaja::where('apertura_venta_id', $aperturaVenta->id)
                ->where('es_anulado', false)
                ->selectRaw("
                COALESCE(SUM(CASE WHEN tipo_movimiento = 'ENTRADA' THEN monto ELSE 0 END), 0) as total_entradas,
                COALESCE(SUM(CASE WHEN tipo_movimiento = 'SALIDA' THEN monto ELSE 0 END), 0) as total_salidas
            ")
                ->first();

            $movimientosNetos = bcsub($movimientos->total_entradas, $movimientos->total_salidas, 2);

            $montoEsperado = bcadd(
                bcadd($aperturaVenta->monto_inicial, $totalVentaEfectivo, 2),
                $movimientosNetos,
                2
            );

            $diferencia = bcsub($request->monto_contado, $montoEsperado, 2);

            $tipoDiferencia = match (true) {
                $diferencia > 0 => 'SOBRANTE',
                $diferencia < 0 => 'FALTANTE',
                default => 'CUADRADO',
            };

            $retiroEfectivo = bcsub($request->monto_contado, $aperturaVenta->monto_inicial, 2);

            if (bccomp($retiroEfectivo, '0', 2) < 0) {
                $retiroEfectivo = '0.00';
            }

            $token = Str::random(40);

            Cache::put("autorizacion_cierre_{$token}", [
                'admin_id' => $admin->id,
                'apertura_venta_id' => $aperturaVenta->id,
            ], now()->addMinutes(10));

            return response()->json([
                'status' => 'ok',
                'nombre_cajero' => $aperturaVenta->cajero->name,
                'numero_caja' => '001',
                'fecha_hora' => $aperturaVenta->fecha_hora_apertura->format('d/m/y H:i:s'),
                'monto_inicial' => $aperturaVenta->monto_inicial,
                'total_entradas' => $movimientos->total_entradas,
                'total_salidas' => $movimientos->total_salidas,
                'total_ventas_efectivo' => $totalVentaEfectivo,
                'total_ventas_transferencia' => $totalVentaTransferencia,
                'token_autorizacion' => $token,
                'monto_esperado' => $montoEsperado,
                'monto_contado' => bcadd($request->monto_contado, 0, 2),
                'diferencia' => $diferencia,
                'retiro_efectivo' => $retiroEfectivo,
                'fondo_siguiente_turno' => bcsub($request->monto_contado, $retiroEfectivo, 2),
                'tipo_diferencia' => $tipoDiferencia,
            ], 200);

        } catch (ModelNotFoundException $m) {
            return response()->json([
                'status' => 'error',
                'message' => 'No tiene una apertura de venta activa para poder cerrarla',
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error interno del servidor',
            ], 500);
        }
    }

    public function cerrarVentaCaja(Request $request)
    {
        try {
            $datos = Cache::get("autorizacion_cierre_{$request->token_autorizacion}");

            if (! $datos) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Token de autorización inválido o expirado',
                ], 401);
            }

            Cache::forget("autorizacion_cierre_{$request->token_autorizacion}");

            $adminId = $datos['admin_id'];

            DB::transaction(function () use ($request, $adminId) {

                $aperturaVenta = AperturaVenta::where('cajero_id', auth()->id())
                    ->where('estado', 'ABIERTA')
                    ->firstOrFail();

                $totalVentaEfectivo = Venta::where('apertura_venta_id', $aperturaVenta->id)
                    ->where('tipo_pago', 'EFECTIVO')
                    ->selectRaw('COALESCE(SUM(efectivo_recibido - cambio), 0) as total')
                    ->value('total');

                $movimientos = MovimientoExternoCaja::where('apertura_venta_id', $aperturaVenta->id)
                    ->where('es_anulado', false)
                    ->selectRaw("
                    COALESCE(SUM(CASE WHEN tipo_movimiento = 'ENTRADA' THEN monto ELSE 0 END), 0) as total_entradas,
                    COALESCE(SUM(CASE WHEN tipo_movimiento = 'SALIDA' THEN monto ELSE 0 END), 0) as total_salidas
                ")
                    ->first();

                $movimientosNetos = bcsub($movimientos->total_entradas, $movimientos->total_salidas, 2);

                $montoEsperado = bcadd(
                    bcadd($aperturaVenta->monto_inicial, $totalVentaEfectivo, 2),
                    $movimientosNetos,
                    2
                );

                $diferencia = bcsub($request->monto_contado, $montoEsperado, 2);

                $tipoDiferencia = match (true) {
                    $diferencia > 0 => 'SOBRANTE',
                    $diferencia < 0 => 'FALTANTE',
                    default => 'CUADRADO',
                };

                if ($tipoDiferencia !== 'CUADRADO' && empty($request->justificacion)) {
                    throw new \InvalidArgumentException('La justificación es obligatoria cuando hay diferencia de caja');
                }

                $aperturaVenta->update([
                    'fecha_hora_cierre' => now(),
                    'monto_esperado' => $montoEsperado,
                    'monto_contado' => $request->monto_contado,
                    'diferencia' => $diferencia,
                    'estado_arqueo' => $tipoDiferencia,
                    'estado' => 'CERRADA',
                    'justificacion' => $request->justificacion,
                    'cerrada_por' => $adminId,
                ]);

                $aperturaCaja = AperturaCaja::where('estado', 'ABIERTO')->firstOrFail();

                $aperturaCaja->update([
                    'fecha_hora_cierre' => now(),
                    'estado' => 'CERRADO',
                    'cerrada_por' => $adminId,
                ]);
            });

            return response()->json([
                'status' => 'ok',
                'message' => 'Caja cerrada correctamente',
            ], 200);

        } catch (\InvalidArgumentException $ex) {
            return response()->json([
                'status' => 'error',
                'message' => $ex->getMessage(),
            ], 422);

        } catch (ModelNotFoundException $m) {
            return response()->json([
                'status' => 'error',
                'message' => 'No se encontró una apertura activa para cerrar',
            ], 404);

        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error interno del servidor',
            ], 500);
        }
    }
}

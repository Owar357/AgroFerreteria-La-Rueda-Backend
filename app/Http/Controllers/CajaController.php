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

class CajaController extends Controller
{
    private function verificarCredenciales(string $email, string $password)
    {
        $usuario = User::where('email', $email)->first();

        if (! $usuario) {
            return [
                'error' => true,
                'response' => response()->json([
                    'status' => 'error',
                    'message' => 'Credenciales inválidas', ], 401),
            ];
        }

        if (! Hash::check($password, $usuario->password)) {
            return [
                'error' => true,
                'response' => response()->json([
                    'status' => 'error',
                    'message' => 'Credenciales inválidas',
                ], 401),
            ];
        }

        if (! $usuario->hasRole('ADMIN')) {

            return [
                'error' => true,
                'response' => response()->json([
                    'status' => 'error',
                    'message' => 'No tienes permisos para realizar esta acción',
                ], 403),
            ];
        }

        return ['error' => false, 'usuario' => $usuario];
    }

    public function abrirCaja(AbrirAperturaCajaRequest $request)
    {
        try {

            $resultado = $this->verificarCredenciales($request->email, $request->password);

            if ($resultado['error']) {
                return $resultado['response'];
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

            $cantidadVentasVinculadas = Venta::whereNull('apertura_venta_id')
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

    public function CuadrarVenta(Request $request)
    {
        try {

           $resultado = $this->verificarCredenciales($request->email, $request->password);
 
           if($resultado['error']){
               return $resultado['response'];
           }           


            $aperturaVenta = AperturaVenta::where('cajero_id', auth()->id())
                ->where('estado', 'ABIERTA')
                ->firstOrFail();

            $totalVentaEfectivo = Venta::where('apertura_venta_id', $aperturaVenta->id)
                ->where('tipo_pago', 'EFECTIVO')
                ->selectRaw('COALESCE(SUM(efectivo_recibido - cambio), 0 ) as total')
                ->value('total');

            $movimientos = MovimientoExternoCaja::where('apertura_venta_id', $aperturaVenta->id)
                ->where('es_anulado', false)
                ->selectRaw("
       COALESCE(SUM(CASE WHEN tipo_movimiento = 'ENTRADA' THEN monto ELSE 0  END), 0 ) as total_entradas,
       COALESCE(SUM(CASE WHEN tipo_movimiento = 'SALIDA' THEN  monto ELSE 0 END), 0) as total_salidas
      ")
                ->first();

            $movimientosNetos = bcsub($movimientos->total_entradas, $movimientos->total_salidas, 2);

            $montoEsperado = bcadd(bcadd($aperturaVenta->monto_inicial, $totalVentaEfectivo, 2),
                $movimientosNetos, 2);

            $diferencia = bcsub($request->monto_contado, $montoEsperado, 2);

            $tipoDiferencia = match (true) {
              $diferencia >  0 => 'SOBRANTE',
              $diferencia <  0 => 'FALTANTE',
              default => 'CUADRADO'
            };

            return response()->json([
                'status' => 'ok',
                'monto_esperado' => $montoEsperado,
                'monto_contado' => $request->monto_contado,
                'diferencia' => $diferencia,
                'tipo_diferencia' => $tipoDiferencia,
            ], 200);

        } catch(ModelNotFoundException $m){
          return response()->json([
              'status' => 'error',
              'message' => 'No tiene una apertura de venta activa para poder cerrarla'
            ],404);
        }catch (\Exception $e) {
            return response()->json([
              'status' => 'error',
              'message' => 'Error interno del servidor'
            ],500);
        }
    }


    public function CerrarVentaCaja(Request $request){
      
    }
}

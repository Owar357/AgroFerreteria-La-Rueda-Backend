<?php

namespace App\Http\Controllers;

use App\Http\Requests\Caja\AbrirAperturaCajaRequest;
use App\Http\Requests\Caja\AbrirAperturaVentaRequest;
use App\Models\AperturaCaja;
use App\Models\AperturaVenta;
use App\Models\User;
use App\Models\Venta;
use Hash;
use Illuminate\Http\Request;

class CajaController extends Controller
{
    public function abrirCaja(AbrirAperturaCajaRequest $request){

      try {

        $usuario =  User::where('email', $request->email)->first();
        if(!$usuario){
            return response()->json([
                'status' => 'error',
                'message' => 'Credenciales inválidas'  
            ],401);
        }

        if(!Hash::check($request->password, $usuario->password)){
            return response()->json([
                "status" => "error",
                "message" => "Credenciales inválidas" 
            ],401);
        }


        if(!$usuario->hasRole('ADMIN')){
            return response()->json([
              "status" => "error",
              "message" => "No tiene permisos para abrir la caja" 
            ],403);
        }

        $yaHayCajaAbierta = AperturaCaja::where('estado', 'ABIERTO')->exists();
        
        
        if($yaHayCajaAbierta){
            return response()->json([
              "status" => "error",
              "message" => "Ya existe una apertura de caja activa " 
            ],422);
        }

        
       
       AperturaCaja::create([
          'fecha_hora_apertura' => now(),
          'estado' => 'ABIERTO',
          'abierta_por' => $usuario->id
        ]);


        return response()->json([
            'status' => 'ok',
            'message' => 'Caja abierta correctamente'  
        ],200);
          
      } catch (\Exception $e) {
        
           return response()->json([
            'status' => 'error',
            'message' => 'Error interno del servidor'  
        ],500);

      }

    }

    public function abrirVenta(AbrirAperturaVentaRequest $request){
      try {
        
          $hayAperturaCaja = AperturaCaja::where('estado','ABIERTO')->first();
          
          if(!$hayAperturaCaja){
            return response()->json([
                "status" => "error",
                "message" => "No se puede aperturar la venta, no hay apertura de caja disponible"
            ],422);
          }


            $yaTieneAperturaVenta = AperturaVenta::where('cajero_id', auth()->id())
            ->where('estado','ABIERTA')
            ->exists();

            if($yaTieneAperturaVenta){
                return response()->json([
                    'status' => 'error',
                    'message' => 'Ya tiene un apertura de venta activa'
                ],422);
            } 

          $abrirVenta = AperturaVenta::create([
                "monto_inicial" => $request->monto_inicial,
                "estado" => 'ABIERTA',
                "apertura_caja_id" => $hayAperturaCaja->id,
                "cajero_id" => auth()->id(),
                "fecha_hora_apertura" => now()
          ]);


          $cantidadVentasVinculadas = Venta::whereNull('apertura_venta_id')
          ->where('vendido_por', auth()->id())
          ->update(['apertura_venta_id' => $abrirVenta->id]);
          


          return response()->json([
            'status' => 'ok',
            'message' => 'Venta aperturada correctamente'
          ],200);

      }catch (\Throwable $th) {
           return response()->json([
                'status' => 'error',
                'message' => 'Error interno del servidor',
            ],500);
      }
    }
}

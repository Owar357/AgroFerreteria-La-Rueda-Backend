<?php

namespace App\Http\Controllers;

use App\Http\Requests\Caja\AbrirAperturaCajaRequest;
use App\Models\AperturaCaja;
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
}

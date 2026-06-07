<?php

namespace App\Http\Controllers;

use App\Http\Requests\Cliente\StoreClienteRequest;
use App\Models\Cliente;
use Error;
use GuzzleHttp\Client;
use Illuminate\Http\Request;

class ClienteController extends Controller
{
    /**
     * Display a listing of the resource.
     */
   public function index()
{
    try {
        $clientes = Cliente::select(
                'id',
                'nombre',
                'telefono',
                'numero_documento',
                'activo'
            )->paginate();

        return response()->json([
            'status' => 'ok',
            'data' => $clientes,
        ], 200);

    } catch (\Exception $e) {
        return response()->json([
            'status' => 'error',
            'message' => 'Error al obtener la lista de clientes'
        ], 500);
    }
}

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreClienteRequest $request)
    {
        try {

            $cliente = Cliente::create([
                ...$request->validated(),
                'registrado_por' => auth()->id(),
            ]);

            return response()->json([
                'status' => 'ok',
                'message' => 'cliente registrado con exito',
                'data' => $cliente,
            ], 201);
        } catch (\Exception $e) {

            return response()->json([
                'status' => 'error',
                'message' => 'No se pudo registrar el cliente',
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

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id) {}

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }

    public function buscarPorDocumento(Request $request){
    
       try {

         $documento = trim($request->input('numero_documento',''));

          if(empty($documento)){
            return response()->json([
                "data" => [],
                "message" => "Ingrese un numero de documento"
            ],422);
          }


         $numeroDocumento = Cliente::Where('numero_documento', $documento)
         ->select('id', 'nombre', 'razon_social')
         ->first();
   
         
         if(!$numeroDocumento){
            return response()->json([
                "status" => "not found"
            ],404);
         }
          
         return response()->json([
            "status" =>"ok",
            "data" => $numeroDocumento
         ],200);

       } catch (\Throwable $th) {
            return response()->json([
            "status" =>"error",
            "message" => "Error interno del servidor"
         ],500);
       }
    }
}

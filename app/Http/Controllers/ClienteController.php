<?php

namespace App\Http\Controllers;

use App\Http\Requests\Cliente\StoreClienteRequest;
use App\Models\Cliente;
use Illuminate\Http\Request;

class ClienteController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try {

            $clientes = Cliente::where('activo')
                ->select(
                    'id',
                    'nombre',
                    'telefono',
                    'numero_documento'
                )->paginate();

            return response()->json([
                'status' => 'ok',
                'data' => $clientes,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error al obtener las lista de clientes'
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
}

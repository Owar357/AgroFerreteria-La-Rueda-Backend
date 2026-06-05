<?php

namespace App\Http\Controllers;

use App\Http\Requests\Presentaciones\StorePresentacionesRequest;
use App\Models\Presentacion;
use Illuminate\Http\Request;

class PresentacionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StorePresentacionesRequest $request)
    {
        try {
            
            Presentacion::create([ 
              ...$request->safe()
            ]); 

            response()->json([
                "status" => "Ok",
                "message" => "Presentacion registrada con exíto" 
            ],200);

        } catch (\Throwable $th) {
           response()->json([
            "status" => "Error",
            "message" => "Error interno en el Servidor"
           ],500);
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
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}

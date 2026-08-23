<?php

namespace App\Http\Controllers;

use App\Models\UnidadMedida;
use Illuminate\Http\Request;

class UnidadMedidaController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
         try {
           
           $query = UnidadMedida::query();

           if($request->filled('magnitud')){
                $query->where('magnitud', $request->magnitud);
           }

           $unidadesMedida = $query->orderBy('nombre','asc')->get();
          
           return response()->json([
            'status' => 'ok',
            'data' => $unidadesMedida
           ],200);

        } catch (\Throwable $th) {
        
            return response()->json([
                'status' => 'error',
                'message' => 'Error interno del servidor',
            ], 500);
        }
    }

   
}

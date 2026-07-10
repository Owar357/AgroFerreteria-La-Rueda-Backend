<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Proveedor;
use Exception;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\DB;
use App\Http\Requests\Proveedor\StoreProveedorRequest;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class ProveedorController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try {

            if (! auth()->user()->hasRole('ADMIN')) {
                return response()->json([
                    'message' => 'No autorizado',
                ], 403);
            }

            $paginator = Proveedor::orderBy('id', 'desc')
              ->paginate(7);

            if ($paginator->isEmpty()) {
                return response()->json([
                    'message' => 'No se encontraron proveedores',
                ], 404);
            }


            $proveedoresFormateados = collect($paginator->items())->map(function ($proveedor) {
                return [
                    'id' => $proveedor->id,
                    'nombre' => $proveedor->nombre,
                    'correo' => $proveedor->correo ?? '—',
                    'telefono' => $proveedor->telefono,
                    'activo' => $proveedor->activo,
                    'direccion' => $proveedor->direccion,
                    'tipo_persona' => $proveedor->tipo_persona,
                ];
        });

            // Retornamos la estructura limpia para PrimeVue
            return response()->json([
                'proveedores' => $proveedoresFormateados,
                'total' => $paginator->total(),
                'per_page' => $paginator->perPage(),
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage()
            ], 200);


        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al obtener proveedores',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreProveedorRequest $request)
    {

      if (! auth()->user()->hasRole('ADMIN')) {
                return response()->json([
                    'message' => 'No autorizado',
                ], 403);
            }

        $request->validate([
            'nombre' => 'required',
            'direccion' => 'required',
            'correo' => 'nullable|unique:proveedores,correo',
            'telefono' => 'required',
            'tipo_persona' => 'required',
            'activo' => 'nullable'
        ]);


        DB::beginTransaction();
        try {
            $proveedor = Proveedor::create([
            ...$request->validated(),
            ]);
            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Proveedor registrado exitosamente',
                'data' => $proveedor
            ], 210);

        } catch (Exception $e) {
            DB::rollBack();

            return response()->json([
                'status' => 'error',
                'message' => 'Ocurrió un error y no se pudo registrar el proveedor',
                'errorMessage' => $e->getMessage()
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

    
    public function traerNombreProveedores(){
        try {

           $proveedores = Proveedor::select('id','nombre')->where('activo', true)
           ->get();


           return response()->json([
            'status' => 'ok',
            'data' => $proveedores
           ],200);


        } catch (\Throwable $th) {
            return response()->json([
            'status' => 'error',
            'message' => 'Error interno del servidor'
           ],500);
        }
    }

    public function desactivarProveedor(int $id){

       try {

          $proveedor = Proveedor::findOrFail($id);

          $proveedor->activo = false;
          $proveedor->save();


          return  response()->json([
            "status" => "ok",
            "message" => "Proveedor desactivado correctamente"
          ],200);
          
       } catch (ModelNotFoundException $mdn) {
          return  response()->json([
            "status" => "error",
            "message" => "El proveedor no existe"
          ],404);
       }catch ( \Throwable $e) {
        return  response()->json([
            "status" => "error",
            "message" => "Error interno en el servidor"
          ],500);
       }

    }
}

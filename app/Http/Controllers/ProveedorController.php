<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Proveedor;
use Exception;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\DB;

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

            if ($Proveedor->isEmpty()) {
                return response()->json([
                    'message' => 'No se encontraron proveedor',
                ], 404);
            }

            // Mapeamos los campos para asegurarnos de que la propiedad 'estado' 
            // concuerde exactamente con los textos 'Activo' o 'Inactivo' de tu frontend.
            $proveedoresFormateados = collect($paginator->items())->map(function ($proveedor) {
                return [
                    'id' => $proveedor->id,
                    'nombre' => $proveedor->nombre,
                    'correo' => $proveedor->correo ?? '—',
                    'telefono' => $proveedor->telefono,
                    // Si en tu BD guardas un booleano (1/0) o string, lo homologamos a 'Activo'/'Inactivo'
                    'estado' => $proveedor->activo ? 'Activo' : 'Inactivo',
                    'direccion' => $proveedor->direccion,
                    'tipo_persona' => $proveedor->tipo_persona,
                    'nrc' => $proveedor->nrc,
                    'nit' => $proveedor->nit,
                    'dui' => $proveedor->dui,
                ];
        });

            // Retornamos la estructura limpia para PrimeVue
            return response()->json([
                'proveedores' => $proveedoresFormateados,
                'total' => $paginator->total(),
                'per_page' => $paginator->perPage(),
                'current_page' => $paginator->currentPage()
            ], 200);

            return response()->json($Proveedor, 200);
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
    public function store(Request $request)
    {
        $request->validate([
            'nombre' => 'required',
            'direccion' => 'required',
            'correo' => 'nullable|unique:proveedores,correo',
            'telefono' => 'required',
            'tipo_persona' => 'required',
            'nrc' => 'nullable|unique:proveedores,nrc',
            'nit' => 'nullable|unique:proveedores,nit',
            'dui' => 'nullable|unique:proveedores,dui',
            'activo' => 'nullable'
        ]);


        DB::beginTransaction();

        try {
            $proveedor = Proveedor::create([
                'nombre' => $request->nombre,
                'direccion' => $request->direccion,
                'correo' => $request->correo,
                'telefono' => $request->telefono,
                'tipo_persona' => $request->tipo_persona,
                'nrc' => $request->nrc,
                'nit' => $request->nit,
                'dui' => $request->dui,
                'activo' => $request->json('activo', true),
            ]);
            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Proveedor registrado exitosamente',
                'data' => $proveedor
            ], 210);

        } catch (Exception $e) {
            DB::rollBack();

            return reponse()->json([
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

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}

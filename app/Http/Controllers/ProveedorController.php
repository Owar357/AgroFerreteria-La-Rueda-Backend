<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Proveedor;
use Exception;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\DB;
use App\Http\Requests\Proveedor\StoreProveedorRequest;
use App\Http\Requests\Proveedor\UpdateProveedorRequest;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class ProveedorController extends Controller
{
    public function index(Request $request)
    {
        try {
            if (! auth()->user()->hasRole('ADMIN')) {
                return response()->json([
                    'message' => 'No autorizado',
                ], 403);
            }

            $perPage = $request->input('per_page', 7);
            $page = $request->input('page', 1);
            $search = trim($request->input('search', ''));
            $estado = $request->input('estado');

            $query = Proveedor::query();

            $query->when(!empty($search), function ($q) use ($search) {
                $q->where(function ($subQ) use ($search) {
                    $subQ->where('nombre', 'ilike', "%{$search}%")
                         ->orWhere('correo', 'ilike', "%{$search}%");
                });
            });

            if ($estado !== null && $estado !== 'TODOS') {
                $query->where('activo', filter_var($estado, FILTER_VALIDATE_BOOLEAN));
            }

            $paginator = $query->orderBy('nombre', 'asc')
                ->orderByDesc('id')
                ->paginate($perPage, ['*'], 'page', $page);

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

    public function store(StoreProveedorRequest $request)
    {
        if (! auth()->user()->hasRole('ADMIN')) {
            return response()->json([
                'message' => 'No autorizado',
            ], 403);
        }

        DB::beginTransaction();
        try {
            $proveedor = Proveedor::create([
                ...$request->validated()
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
            ], 500);
        }
    }

    public function show(string $id)
    {
        //
    }

    public function update(UpdateProveedorRequest $request, Proveedor $proveedore)
    {
        try {
            if (! auth()->user()->hasRole('ADMIN')) {
                return response()->json([
                    'message' => 'No autorizado',
                ], 403);
            }

            $proveedore->update($request->validated());

            return response()->json([
                'status' => 'ok',
                'message' => 'proveedor editado correctamente',
                'data' => $proveedore,
            ], 200);

        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error interno del servidor'
            ], 500);
        }
    }

    public function traerNombreProveedores()
    {
        try {
           $proveedores = Proveedor::select('id','nombre')->where('activo', true)->get();

           return response()->json([
            'status' => 'ok',
            'data' => $proveedores
           ], 200);
        } catch (\Throwable $th) {
            return response()->json([
            'status' => 'error',
            'message' => 'Error interno del servidor'
           ], 500);
        }
    }

    public function desactivarProveedor(int $id)
    {
       try {
          if (! auth()->user()->hasRole('ADMIN')) {
              return response()->json(['message' => 'No autorizado'], 403);
          }

          $proveedor = Proveedor::findOrFail($id);
          $proveedor->activo = false;
          $proveedor->save();

          return response()->json([
            "status" => "ok",
            "message" => "Proveedor desactivado correctamente"
          ], 200);

       } catch (ModelNotFoundException $mdn) {
          return response()->json([
            "status" => "error",
            "message" => "El proveedor no existe"
          ], 404);
       } catch ( \Throwable $e) {
        return response()->json([
            "status" => "error",
            "message" => "Error interno en el servidor"
          ], 500);
       }
    }

    public function ActivarProveedor(int $id)
    {
       try {
          if (! auth()->user()->hasRole('ADMIN')) {
              return response()->json(['message' => 'No autorizado'], 403);
          }

          $proveedor = Proveedor::findOrFail($id);
          $proveedor->activo = true;
          $proveedor->save();

          return response()->json([
            "status" => "ok",
            "message" => "Proveedor activado exitosamente."
          ], 200);

       } catch (ModelNotFoundException $mdn) {
          return response()->json([
            "status" => "error",
            "message" => "El proveedor no existe"
          ], 404);
       } catch ( \Throwable $e) {
        return response()->json([
            "status" => "error",
            "message" => "Error interno en el servidor"
          ], 500);
       }
    }
}
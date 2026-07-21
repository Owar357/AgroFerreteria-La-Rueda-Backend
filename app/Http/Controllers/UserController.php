<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use App\Http\Requests\User\StoreUserRequest;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        try {

            if (! auth()->user()->hasRole('ADMIN')  ) {
                return response()->json([
                    'message' => 'No autorizado',
                ], 403);
            }

            $perPage = $request->get('per_page', 5);
            $page = $request->get('page', 1);

            $users = User::with(['roles:name', 'registradoPor:id,name'])
                ->select('id', 'name', 'email', 'activo', 'registrado_por', 'created_at')
                ->orderBy('id', 'desc')
                ->paginate($perPage, ['*'], 'page', $page);
            if ($users->isEmpty()) {
                return response()->json([
                    'message' => 'No se encontraron usuarios',
                ], 404);
            }

            // Ocultamos el pivot de roles en cada usuario
            $users->each(function ($user) {
                $user->roles->makeHidden(['pivot']);
            });

            return response()->json([
                'data'=> $users->items(),
                'total' => $users->total(),
                'per_page'=> $users->perPage(),
                'current_page'=> $users->currentPage(),
                'last_page' => $users->lastPage(),
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al obtener usuarios',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreUserRequest $request)
    {
        try {

            if (! auth()->user()->hasRole('ADMIN')) {
                return response()->json([
                    'message' => 'No autorizado',
                ], 403);
            }

            $user = User::create([
                ...$request->validated(),
                'registrado_por' => auth()->id(),
            ]);

            $user->assignRole($request->rol);

            return response()->json([
                'message' => 'Usuario creado correctamente',
                'user' => $user->fresh()->load(['roles:name', 'registradoPor:id,name'])
            ], 201);

        } catch (ValidationException $e) {
            return response()->json([
                'message'=> 'Error de validación',
                'errors'=> $e->errors(),
            ], 422);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al crear usuario',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    public function show(string $id) {}

    public function update(Request $request, string $id) {}

    public function destroy(string $id) {}
}

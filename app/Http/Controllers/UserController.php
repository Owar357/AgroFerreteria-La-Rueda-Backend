<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use App\Http\Requests\User\StoreUserRequest;
use App\Http\Requests\User\UpdateUserRequest;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        try {

            if (! auth()->user()->hasRole('ADMIN')) {
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
                'data' => $users->items(),
                'total' => $users->total(),
                'per_page' => $users->perPage(),
                'current_page' => $users->currentPage(),
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
                'message' => 'Error de validación',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al crear usuario',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    public function show(string $id) {}

    public function update(UpdateUserRequest $request, string $id)
    {

        try {
            //Validamos que solo administradores podran actulizar datos de usuario

            if (! auth()->user()->hasRole('ADMIN')) {
                return response()->json([
                    'message' => 'No autorizado',
                ], 403);
            }

            $user = User::find($id);

            if (! $user) {
                return response()->json([
                    'message' => 'Usuario no encontrado',
                ], 404);
            }

            $user->update($request->validated());

            return response()->json([
                'message' => 'Usuario actualizado correctamente',
                'user' => $user->fresh()->load(['roles:name', 'registradoPor:id,name']),
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al actualizar la categoría',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    public function destroy(string $id) {}


    public function desactivarUsuario(string $id)
    {
        try {
            if (! auth()->user()->hasRole('ADMIN')) {
                return response()->json([
                    'message' => 'No autorizado',
                ], 403);
            }
            $user = User::find($id);

            if (! $user) {
                return response()->json([
                'message' => 'Usuario no encontrado',
                ], 404);
            }

            if (auth()->id() == $user->id) {
                return response()->json([
                'message' => 'No puedes desactivar tu propio usuario',
                ], 400);
            }

            if (! $user->activo) {
                return response()->json([
                'message' => 'El usuario ya se encuentra desactivado',
                ], 400);
            }

            $user->update([
                'activo' => false,
            ]);

            return response()->json([
            'message' => 'Usuario desactivado correctamente',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al desactivar el usuario',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}

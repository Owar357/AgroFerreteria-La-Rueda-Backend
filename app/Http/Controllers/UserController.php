<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
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

            $user = User::with(['roles' => function ($query) {
                $query->select('name');
            }])
                ->select('id', 'name', 'email', 'activo', 'registrado_por')
                ->orderBy('id', 'desc')
                ->get();

            if ($user->isEmpty()) {
                return response()->json([
                    'message' => 'Usuario no encontrados',
                ], 404);
            }

            $user->each(function ($user) {
                $user->roles->makeHidden(['pivot']);
            });

            return response()->json($user, 200);

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
    public function store(Request $request)
    {
        try {
            if (! auth()->user()->hasRole('ADMIN')) {
                return response()->json([
                    'message' => 'No autorizado',
                ], 403);
            }

            $request->validate([
                'name' => 'required|string|min:3|max:100',
                'email' => 'required|email|unique:users,email',
                'password' => 'required|min:8',
                'rol' => 'required|exists:roles,name',
              ],
             [
                'email.unique' => 'El correo ya está registrado',
                'rol.exists' => 'El rol no existe',
             ]
            );

            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'activo' => true,
                'registrado_por' => auth()->id(),
            ]);
            // Parra aggrreegar el rol
            $user->assignRole($request->rol);

        } catch (ValidationException $e) {

            return response()->json([
                'message' => 'Error de validación',
                'errors' => $e->errors(),
            ], 422);

        } catch (\Exception $e) {

            return response()->json([
                'message' => 'Error al crear usuario',
                'error' => $e->getMessage(),
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

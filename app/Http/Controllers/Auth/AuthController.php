<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    // Función login
    public function login(Request $request)
    {
        $credenciales = $request->only('email', 'password');

        if (! $token = Auth::attempt($credenciales)) {
            return response()->json([
                'message' => 'Credenciales inválidas',
            ], 401);
        }

        // en caso de exitoso retornamos el token
        return $this->responseWithToken($token);
    }

    // Para detectar el token
    protected function responseWithToken($token)
    {
        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'user' => [
                'id' => auth()->user()->id,
                'name' => auth()->user()->name,
                'email' => auth()->user()->email,
            ],
            'roles' => auth()->user()->getRoleNames(),
            'expires_in' => auth()->factory()->getTTL() * 30,
        ]);
    }

    public function me()
    {
        return response()->json(auth()->user());
    }

    // método para invalidar un token (logout)
    public function logout()
    {
        auth()->logout();

        return response()->json([
            'message' => 'Sesión cerrada correctamente',
        ]);
    }

    // método para refrescar el token
    public function refresh()
    {
        return $this->responseWithToken(auth()->refresh());
    }
}

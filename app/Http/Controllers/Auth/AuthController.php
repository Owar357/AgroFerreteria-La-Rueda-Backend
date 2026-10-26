<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    
    public function login(Request $request)
    {
        $credenciales = $request->only('email', 'password');

        if (! $token = Auth::attempt($credenciales)) {
            return response()->json([
                'message' => 'Credenciales inválidas',
            ], 401);
        }

        if (! auth()->user()->activo) {
            auth()->logout(); 

            return response()->json([
                'message' => 'Su cuenta ha sido desactivada.'
            ], 403);
        }
        
        return $this->responseWithToken($token);
    }

    
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
            'expires_in' => auth()->factory()->getTTL() * 60,
        ]);
    }

    public function me()
    {
        return response()->json(auth()->user());
    }

    
    public function logout()
    {
        auth()->logout();

        return response()->json([
            'message' => 'Sesión cerrada correctamente',
        ]);
    }

   
    public function refresh()
    {
        if(! auth()->user()->activo){
            auth()->logout();

            return response()->json([
            'message' => 'Su cuenta a sido desactivada'
        ],403);

        }

        return $this->responseWithToken(auth()->refresh());
    }
}

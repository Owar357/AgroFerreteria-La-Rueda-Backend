<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AutenticacionAdminService{
    
    public function verificarCredencial(string $email, string $password): array
    {
        $usuario = User::where('email', $email)->first();

        if (! $usuario || ! Hash::check($password, $usuario->password)) {
            return ['error' => true, 'message' => 'Credenciales inválidas', 'code' => 401];
        }

        if (! $usuario->hasRole('ADMIN')) {
            return ['error' => true, 'message' => 'No tienes permisos para realizar esta acción', 'code' => 403];
        }

        return ['error' => false, 'usuario' => $usuario];
    }
}
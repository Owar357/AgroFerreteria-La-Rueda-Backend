<?php

namespace App\Http\Requests\Caja;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class Anularmovimientorequest extends FormRequest
{
    
    public function authorize(): bool
    {
        return true;
    }

   
    public function rules(): array
    {
        return [
              'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ];
    }

     public function messages(): array
    {
        return [
            'email.required' => 'El email del administrador es obligatorio.',
            'email.email' => 'El email no tiene un formato válido.',
            'password.required' => 'La contraseña del administrador es obligatoria.',
        ];
    }
}

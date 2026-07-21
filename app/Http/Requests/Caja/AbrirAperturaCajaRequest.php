<?php

namespace App\Http\Requests\Caja;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class AbrirAperturaCajaRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
     public function rules(): array
    {
        return [
            "email" => "required|email",
            "password" => "required|string"
        ];
    }

    
    public function messages()
    {
        return [
            "email.required" => "El email es obligatario",
            "email.email" => "El email no tiene el formato válido",
            "password.required" => "La contraseña es obligatoria",
        ];
    }   
    
}

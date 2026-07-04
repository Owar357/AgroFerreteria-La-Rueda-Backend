<?php

namespace App\Http\Requests\User;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreUserRequest extends FormRequest
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
            'name' => 'required|string|min:3|max:100|regex:/^[\pL\s]+$/u|unique:users,name',
            'email' => 'required|email|unique:users,email|regex:/^[a-z0-9_.+\-]+@[a-z0-9\-]+\.[a-z]{2,}$/',
            'pin_caja' => 'required|max:6|min:6',
            'password' => 'required|min:8',
            'rol' => 'required|exists:roles,name',
        ];
    }

    public function messages(): array
    {
        return [
            'name.unique' => 'Ya existe un usuario con este nombre',
            'name.required' => 'El nombre es obligatorio.',
            'name.regex' => 'No se permite el ingreso de datos numericos',
            'email.unique' => 'Ya existe un usuario con este correo',
            'email.email' => 'Ingresar el correo con el formato correcto(@gmail.com, @hotmail.com, etc.',
            'email.regex' => 'No se permite el ingreso de mayusculas',
            'pin_caja.required' => 'Por favor ingresar el pin de caja',
            'pin_caja.max' => 'Numero de dijitos excedido, favor ingresar un maximo 6 numeros',
            'pin_caja.min' => 'Por favor ingresar un minimo de 6 numeros',
            'password.required' => 'Contraseñá obligatoria.',
            'rol.required' => 'El rol es obligatorio.',
        ];
    }
}






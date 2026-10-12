<?php

namespace App\Http\Requests\User;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use App\Rules\PasswordNoUsadaPorOtro;

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
            'email' => [
                    'required', 'string', 'max:255',
                    'regex:/^[A-Za-z0-9]+([._-][A-Za-z0-9]+)*@[A-Za-z0-9]+(-[A-Za-z0-9]+)*(\.[A-Za-z]{2,3})?\.[A-Za-z]{2,}$/',
                'unique:users,email',
            ],
            'password' => ['required', 'string', 'min:8', new PasswordNoUsadaPorOtro()],
            'rol' => 'required|exists:roles,name',
        ];
    }

    public function messages(): array
    {
        return [
          'name.required' => 'El nombre es obligatorio.',
            'name.min' => 'El nombre debe tener al menos 3 caracteres.',
            'name.max' => 'El nombre no puede superar los 50 caracteres.',
            'name.regex' => 'El nombre solo puede contener letras y espacios.',
            'name.unique' => 'Ya existe un usuario con este nombre.',
            'email.required' => 'El correo es obligatorio.',
            'email.max' => 'El correo no puede superar los 255 caracteres.',
            'email.regex' => 'Correo inválido. Solo se permiten letras, números y los símbolos . _ -',
            'email.unique' => 'Ya existe un usuario con este correo.',
            'password.required' => 'La contraseña es obligatoria.',
            'password.min' => 'La contraseña debe tener al menos 8 caracteres.',
            'rol.required' => 'El rol es obligatorio.',
            'rol.exists' => 'El rol seleccionado no es válido.',
        ];
    }
}






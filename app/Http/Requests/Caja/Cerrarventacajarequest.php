<?php

namespace App\Http\Requests\Caja;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class Cerrarventacajarequest extends FormRequest
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
             'token_autorizacion' => ['required', 'string', 'size:40'],
            'justificacion' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'token_autorizacion.required' => 'Se requiere la autorización de un administrador para continuar.',
        'token_autorizacion.size' => 'El código de autorización ingresado no es válido.',
            'justificacion.max' => 'La justificación no puede superar los 255 caracteres.',
        ];
    }
}

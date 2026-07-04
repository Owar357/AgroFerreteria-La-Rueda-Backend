<?php

namespace App\Http\Requests\CodigoBarra;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Override;

class StoreCodigoBarraRequest extends FormRequest
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
            'codigo' => 'required|string|max:50|unique:codigos_barras,codigo',
            'presentacion_id' => 'required|exists:presentaciones,id'
        ];
    }


    public function messages(): array
    {
        return [
            'codigo.unique' => 'El código de barra ya existe',
            'codigo.required' => 'Ingresar un codigo de barra',
            'presentacion_id.exists' => 'La presentación no existe',
        ];
    }
}

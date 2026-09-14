<?php

namespace App\Http\Requests\Categoria;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;


class StoreCategoriaRequest extends FormRequest
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
            'nombre' => 'required|string|min:2|max:50|unique:categorias,nombre|regex:/^[\pL\s]+$/u',
            'porcentaje_ganancia_minimo' => 'nullable|numeric|min:0|max:100',
        ];
    }



    public function messages(): array
    {
        return [
            'nombre.unique' => 'Ya existe una categoria con este nombre',
            'nombre.required' => 'El nombre es obligatorio.',
            'nombre.regex' => 'No se permite el ingreso de datos numericos',
            'porcentaje_ganancia_minimo.numeric' => 'El porcentaje de ganancia mínimo debe ser un número.',
            'porcentaje_ganancia_minimo.min' => 'El porcentaje de ganancia mínimo no puede ser menor a 0.',
            'porcentaje_ganancia_minimo.max' => 'El porcentaje de ganancia mínimo no puede superar el 100%.',

        ];
    }
}

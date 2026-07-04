<?php

namespace App\Http\Requests\Categoria;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateCategoriaRequest extends FormRequest
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
            'nombre'=>'required|string|min:2|max:50|regex:/^[\pL\s]+$/u|unique:categorias,nombre,'.$this->route('categoria'),
        ];
    }

    public function messages(): array
    {
        return [
            'nombre.required'=> 'El nombre es obligatorio.',
            'nombre.string'=> 'El nombre debe ser texto.',
            'nombre.min' => 'El nombre debe tener al menos 2 caracteres.',
            'nombre.max' => 'El nombre no puede tener más de 50 caracteres.',
            'nombre.regex' => 'El nombre solo puede contener letras y espacios.',
            'nombre.unique' => 'Ya existe otra categoría con este nombre.',
        ];
    }
}

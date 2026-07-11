<?php

namespace App\Http\Requests\Producto;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateProductoRequest extends FormRequest
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
            'codigo' => 'required|string|min:2|max:14|unique:productos,codigo,' . $this->route('producto') . '|regex:/^[A-Za-z0-9-]+$/',
            'nombre' => 'sometimes|string|max:100',
            'fabricante' => 'sometimes|nullable|max:100',
            'categoria_id' => 'sometimes|exists:categorias,id',
        ];
    }

    public function messages(): array
    {
        return [
            'codigo.required' => 'El codigo es obligatorio',
            'codigo.string' => 'El código del producto debe ser un texto.',
            'codigo.min' => 'El código debe tener un mínimo de 2 caracteres.',
            'codigo.max' => 'El código no puede superar los 14 caracteres.',
            'codigo.unique' => 'Ya existe un producto con este código.',
            'codigo.regex' => 'El código solo puede contener letras, números y guiones.',
            'nombre.string' => 'El nombre del producto debe ser un texto.',
            'nombre.max' => 'El nombre del producto no puede superar los 100 caracteres.',
            'fabricante.max' => 'El fabricante no puede superar los 100 caracteres.',
            'categoria_id.exists' => 'La categoría seleccionada no existe.',
        ];
    }
}

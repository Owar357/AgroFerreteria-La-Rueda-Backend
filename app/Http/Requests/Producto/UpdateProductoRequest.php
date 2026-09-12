<?php

namespace App\Http\Requests\Producto;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProductoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {

        $productoId = $this->route('producto');

        return [
            'codigo' => 'sometimes|string|min:2|max:14|unique:productos,codigo,'.$productoId.'|regex:/^[A-Za-z0-9-]+$/',
            'nombre' => 'sometimes|string|max:100',
            'fabricante' => 'nullable|string|max:100',
            'categoria_id' => 'sometimes|exists:categorias,id',
            'porcentaje_ganancia_minimo' => 'sometimes|nullable|numeric|min:0|max:100',
        ];
    }

    public function messages(): array
    {
        return [
            'codigo.string' => 'El código del producto debe ser un texto.',
            'codigo.min' => 'El código debe tener un mínimo de 2 caracteres.',
            'codigo.max' => 'El código no puede superar los 14 caracteres.',
            'codigo.unique' => 'Ya existe un producto con este código.',
            'codigo.regex' => 'El código solo puede contener letras, números y guiones.',

            'nombre.string' => 'El nombre del producto debe ser un texto.',
            'nombre.max' => 'El nombre del producto no puede superar los 100 caracteres.',
            'fabricante.max' => 'El fabricante no puede superar los 100 caracteres.',
            'categoria_id.exists' => 'La categoría seleccionada no existe.',
            'porcentaje_ganancia_minimo.numeric' => 'El porcentaje de ganancia mínimo debe ser un número.',
            'porcentaje_ganancia_minimo.min' => 'El porcentaje de ganancia mínimo no puede ser menor a 0.',
            'porcentaje_ganancia_minimo.max' => 'El porcentaje de ganancia mínimo no puede superar el 100%.',
        ];
    }
}

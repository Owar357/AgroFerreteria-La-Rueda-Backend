<?php

namespace App\Http\Requests\Producto;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Override;

class StoreProductoRequest extends FormRequest
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
            'codigo' => 'required|string|min:2|max:14|unique:productos,codigo|regex:/^[A-Za-z0-9-]+$/',
            'nombre' => 'required|string|max:100',
            'fabricante' => 'nullable|max:100',
            'tipo_producto' => 'required|in:UNIDAD FIJA,GRANEL',
            'unidad_base' => 'required',
            'categoria_id' => 'required|exists:categorias,id',
            'presentaciones' => 'required|array|min:1',
            'presentaciones.*.nombre' => 'nullable|string|max:150',
            'presentaciones.*.factor_conversion' => 'required|numeric|min:0',
            'presentaciones.*.precio_venta' => 'required|numeric|min:0',
            'presentaciones.*.codigos_barra' => 'required|array|min:1',
            'presentaciones.*.codigos_barra.*.codigo' => 'required|string|unique:codigos_barras,codigo',

        ];
    }


    public function messages(): array
    {
        return [
            'codigo.required' => 'El código del producto es obligatorio.',
            'codigo.string' => 'El código del producto debe ser un texto.',
            'codigo.min' => 'El código debe tener un mínimo de 2 caracteres.',
            'codigo.max' => 'El código no puede superar los 14 caracteres.',
            'codigo.unique' => 'Ya existe un producto con este código.',
            'codigo.regex' => 'El código solo puede contener letras, números y guiones.',

            'nombre.required' => 'El nombre del producto es obligatorio.',
            'nombre.string' => 'El nombre del producto debe ser un texto.',
            'nombre.max' => 'El nombre del producto no puede superar los 100 caracteres.',

            'fabricante.max' => 'El fabricante no puede superar los 100 caracteres.',

            'tipo_producto.required' => 'Debe seleccionar el tipo de producto.',
            'tipo_producto.in' => 'El tipo de producto seleccionado no es válido.',

            'unidad_base.required' => 'Debe seleccionar la unidad base del producto.',

            'categoria_id.required' => 'Debe seleccionar una categoría.',
            'categoria_id.exists' => 'La categoría seleccionada no existe.',

            'presentaciones.required' => 'Debe agregar al menos una presentación.',
            'presentaciones.array' => 'Las presentaciones deben enviarse en un formato válido.',
            'presentaciones.min' => 'Debe registrar al menos una presentación.',
            'presentaciones.*.nombre.string' => 'El nombre de la presentación debe ser un texto.',
            'presentaciones.*.nombre.max' => 'El nombre de la presentación no puede superar los 150 caracteres.',
            'presentaciones.*.factor_conversion.required' => 'Debe ingresar el factor de conversión.',
            'presentaciones.*.factor_conversion.min' => 'El factor de conversión no puede ser menor que cero.',
            'presentaciones.*.precio_venta.required' => 'Debe ingresar el precio de venta.',
            'presentaciones.*.precio_venta.numeric' => 'El precio de venta debe ser un número.',
            'presentaciones.*.precio_venta.min' => 'El precio de venta no puede ser menor que cero.',
            'presentaciones.*.codigos_barra.required' => 'Debe agregar al menos un código de barras.',
            'presentaciones.*.codigos_barra.array' => 'Los códigos de barras deben enviarse en un formato válido.',
            'presentaciones.*.codigos_barra.min' => 'Debe registrar al menos un código de barras.',
            'presentaciones.*.codigos_barra.*.codigo.required' => 'El código de barras es obligatorio.',
            'presentaciones.*.codigos_barra.*.codigo.string' => 'El código de barras debe ser un texto.',
            'presentaciones.*.codigos_barra.*.codigo.unique' => 'El código de barras ya se encuentra registrado.',

        ];
    }
}

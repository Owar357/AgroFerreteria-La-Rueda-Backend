<?php

namespace App\Http\Requests\Presentacion;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdatePreciosMasivoRequest extends FormRequest
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
            'precios'                           => ['required', 'array', 'min:1'],
            'precios.*.presentacion_id'        => ['required', 'integer', 'exists:presentaciones,id'],
            'precios.*.precio_venta_sugerido'  => ['required', 'numeric', 'gt:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'precios.required'                          => 'La lista de precios a actualizar es obligatoria.',
            'precios.array'                             => 'El formato de los precios debe ser una lista.',
            'precios.min'                               => 'Debe incluir al menos una presentación para actualizar.',
            'precios.*.presentacion_id.required'        => 'El ID de la presentación es obligatorio.',
            'precios.*.presentacion_id.exists'          => 'Una de las presentaciones seleccionadas no existe.',
            'precios.*.precio_venta_sugerido.required' => 'El precio sugerido es obligatorio.',
            'precios.*.precio_venta_sugerido.numeric'  => 'El precio sugerido debe ser un número válido.',
            'precios.*.precio_venta_sugerido.gt'       => 'El precio sugerido debe ser mayor a 0.',
        ];
    }
}

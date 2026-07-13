<?php

namespace App\Http\Requests\Presentaciones;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdatePresentacionesRequest extends FormRequest
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
        'nombre' => 'sometimes|string|max:255',

        'factor_conversion' => 'sometimes|numeric|min:1',

        'precio_venta' => 'sometimes|numeric|min:0',
    ];
    }

    public function messages():array
    {
        return [
        'nombre.string' => 'El nombre debe ser texto.',
        'nombre.max' => 'El nombre no puede tener más de 255 caracteres.',
        'factor_conversion.numeric' => 'El factor de conversión debe ser un número.',
        'factor_conversion.min' => 'El factor de conversión debe ser mayor a 0.',
        'precio_venta.numeric' => 'El precio de venta debe ser un número.',
        'precio_venta.min' => 'El precio de venta no puede ser negativo.',
    ];
    }
}

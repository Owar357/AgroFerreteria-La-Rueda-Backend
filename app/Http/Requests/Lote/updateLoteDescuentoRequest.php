<?php

namespace App\Http\Requests\Lote;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Override;

class updateLoteDescuentoRequest extends FormRequest
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
            'porcentaje_descuento' => 'nullable|numeric|min:0|max:100',
        ];
    }

    #[Override]
    public function messages()
    {
        return [
            'porcentaje_descuento.numeric' => 'EL porcentaje de descuento deber ser un valor numerico.',
            'porcentaje_descuento.min' => 'El pocentaje de descuento no puede ser menor a 0.',
            'porcentaje_descuento.max' => 'El porcentaje de descuento no puede ser mayor a 100.',
        ];
    }
}

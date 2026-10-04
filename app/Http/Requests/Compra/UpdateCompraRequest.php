<?php

namespace App\Http\Requests\Compra;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateCompraRequest extends FormRequest
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
            'estado_pago' => 'required|in:PAGADO',
        ];
    }

    public function messages(): array
    {
        return [
            'estado_pago.required' => 'El estado de pago es obligatorio.',
            'estado_pago.in'       => 'Solo se permite cambiar el estado a PAGADO.',
        ];
    }
}

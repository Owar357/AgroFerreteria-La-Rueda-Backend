<?php

namespace App\Http\Requests\Caja;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Override;

class AbrirAperturaVentaRequest extends FormRequest
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
            'monto_inicial' => 'required|numeric|min:0.01', 
        ];
    }

    
    public function messages()
    {
        return[
            'monto_inicial.required' => 'El monto inicial es requerido para apertura la venta',
            'monto_inicial.numeric' => 'EL monto inicial deber ser un valor númerico valido',
            'monto_inicial.min' => 'El monto incial debe ser un valor positivo diferente de 0' 
        ];
    }
}

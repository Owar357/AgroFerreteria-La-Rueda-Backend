<?php

namespace App\Http\Requests\MovimientoCaja;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Override;

class StoreMovimientoCaja extends FormRequest
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
            'tipo_movimiento' => 'required|in:ENTRADA,SALIDA',
            'monto'=> 'required|numeric|min:0.01',
            'motivo' => 'required|string|max:255'
        ];
    }

    
    public function messages()
    {
        return [
            'tipo_movimiento.required' => 'El tipo de movimiento es requerido',
            'tipo_movimiento.in' => 'El tipo de movimiento solo debe ser uno de los 2 aceptados',
            'monto.required' => 'El monto es requerido',
            'monto.numeric' => 'El monto debe ser un valor númerico',
            'monto.min' => 'El monto debe ser igual o superior a 0.01',
            'motivo.required' => 'El motivo del movimiento es obligatorio',
            'motivo.max' => 'El máximo de caracteres permitidos para la descripción es de 255'
        ];
    }
}

<?php

namespace App\Http\Requests\Caja;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MovimientoExternoRequest extends FormRequest
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
            'tipo_movimiento' => "required|in:ENTRADA,SALIDA",
            'monto' => "required|numeric|min:0.01",
            'motivo' =>  "required|string|max:255",
            'origen' => [
                'required',
                'in:CAJA_CHICA,VENTAS',
                Rule::prohibitedIf(
                    $this->tipo_movimiento === 'ENTRADA' && $this->origen === 'VENTAS'
                ),
            ], 
        ];
    }

     public function messages(): array
    {
        return [
            'tipo_movimiento.required' => 'Debe indicar si es una entrada o salida de dinero.',
            'tipo_movimiento.in' => 'El tipo de movimiento debe ser ENTRADA o SALIDA.',
            'origen.required' => 'Debe indicar el origen del dinero (caja chica o ventas).',
            'origen.in' => 'El origen debe ser CAJA_CHICA o VENTAS.',
            'origen.prohibited' => 'Una entrada de dinero solo puede provenir de caja chica, no de ventas.',
            'monto.required' => 'El monto es requerido.',
            'monto.numeric' => 'El monto solo puede contener valores numéricos.',
            'monto.min' => 'El monto debe ser mayor a 0.',
            'motivo.required' => 'Debe indicar el motivo del movimiento.',
            'motivo.string' => 'El motivo debe ser texto.',
            'motivo.max' => 'El motivo no puede tener más de 255 caracteres.',
        ];
    }
        
}

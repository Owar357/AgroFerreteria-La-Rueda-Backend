<?php

namespace App\Http\Requests\Caja;

use Illuminate\Foundation\Http\FormRequest;

class MovimientoExternoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * 'origen' no se recibe: el servidor siempre lo fija en VENTAS
     * (gaveta única, sin caja chica separada).
     *
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'tipo_movimiento' => ['required', 'in:ENTRADA,SALIDA'],
            'monto' => ['required', 'numeric', 'min:0.01', 'max:999999999999'],
            'motivo' => ['required', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'tipo_movimiento.required' => 'Debe indicar si es una entrada o salida de dinero.',
            'tipo_movimiento.in' => 'El tipo de movimiento debe ser ENTRADA o SALIDA.',
            'monto.required' => 'El monto es requerido.',
            'monto.numeric' => 'El monto solo puede contener valores numéricos.',
            'monto.min' => 'El monto debe ser mayor a 0.',
            'monto.max' => 'El monto excede el máximo permitido.',
            'motivo.required' => 'Debe indicar el motivo del movimiento.',
            'motivo.string' => 'El motivo debe ser texto.',
            'motivo.max' => 'El motivo no puede tener más de 255 caracteres.',
        ];
    }
}
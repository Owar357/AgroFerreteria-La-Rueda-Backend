<?php

namespace App\Http\Requests\Caja;

use Illuminate\Validation\Validator;
use App\Services\CajaService; 
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
            // Conteo por denominación: { "c1": 0, "c5": 2, ..., "b100": 1 }
            'denominaciones' => ['required_without:monto_inicial', 'array'],
            'denominaciones.*' => ['nullable', 'integer', 'min:0', 'max:1000000'],
 
            // TRANSICIÓN: se elimina cuando el frontend envíe siempre 'denominaciones'
            'monto_inicial' => ['required_without:denominaciones', 'numeric', 'min:0.01'],
 
            // Obligatoria (validada en el controlador) si el conteo es distinto del fondo fijo
            'justificacion_apertura' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                $invalidas = array_diff(
                    array_keys((array) $this->input('denominaciones', [])),
                    array_keys(CajaService::DENOMINACIONES)
                );
 
                if ($invalidas !== []) {
                    $validator->errors()->add(
                        'denominaciones',
                        'Contiene denominaciones no válidas: '.implode(', ', $invalidas).'.'
                    );
                }
            },
        ];
    }

    
    public function messages()
    {
        return [
            'denominaciones.required_without' => 'Debe registrar el conteo de monedas y billetes.',
            'denominaciones.array' => 'El conteo de denominaciones no tiene un formato válido.',
            'denominaciones.*.integer' => 'La cantidad de cada denominación debe ser un número entero.',
            'denominaciones.*.min' => 'La cantidad de cada denominación no puede ser negativa.',
            'monto_inicial.required_without' => 'El monto inicial es requerido para aperturar la venta.',
            'monto_inicial.numeric' => 'El monto inicial debe ser un valor numérico válido.',
            'monto_inicial.min' => 'El monto inicial debe ser un valor positivo diferente de 0.',
            'justificacion_apertura.max' => 'La justificación de apertura no puede superar los 500 caracteres.',
        ];
    }
}

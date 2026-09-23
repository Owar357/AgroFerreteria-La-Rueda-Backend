<?php

namespace App\Http\Requests\Caja;


use App\Services\CajaService;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class CuadrarVentaRequest extends FormRequest
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

            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
 
            // Conteo final por denominación: { "c1": 0, "c5": 2, ..., "b100": 1 }
            'denominaciones' => ['required_without:monto_contado', 'array'],
            'denominaciones.*' => ['nullable', 'integer', 'min:0', 'max:1000000'],
 
            // TRANSICIÓN: se elimina cuando el frontend envíe siempre 'denominaciones'
            'monto_contado' => ['required_without:denominaciones', 'numeric', 'min:0'],
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
 
    public function messages(): array
    {
        return [
            'email.required' => 'El email del administrador es obligatorio.',
            'email.email' => 'El email no tiene un formato válido.',
            'password.required' => 'La contraseña del administrador es obligatoria.',
            'denominaciones.required_without' => 'Debe registrar el conteo de monedas y billetes.',
            'denominaciones.array' => 'El conteo de denominaciones no tiene un formato válido.',
            'denominaciones.*.integer' => 'La cantidad de cada denominación debe ser un número entero.',
            'denominaciones.*.min' => 'La cantidad de cada denominación no puede ser negativa.',
            'monto_contado.required_without' => 'El monto contado es obligatorio.',
            'monto_contado.numeric' => 'El monto contado debe ser un valor numérico válido.',
            'monto_contado.min' => 'El monto contado no puede ser negativo.',
        ];
    }
}


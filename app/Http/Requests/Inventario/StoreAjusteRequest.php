<?php

namespace App\Http\Requests\Inventario;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Override;

class StoreAjusteRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return truez;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'tipo_ajuste' => 'required|in:INCREMENTO,DISMINUCION,REEVALUACION',
            'motivo' => 'required|string|max:100',
            'observaciones' => 'nullable|string',
            'detalles' => 'required|array|min:1',
            'detalles.*.lote_id' => 'required|exists:lotes,id',
            'detalles.*.cantidad_fisica' => 'required_if:tipo_ajuste,INCREMENTO,DISMINUCION|numeric|min:0',
            'detalles.*.costo_nuevo' => 'required_if:tipo_ajuste,REEVALUACION|numeric|min:0.0001',
        ];
    }


    #[Override]
    public function messages()
    {
        return [
            'tipo_ajuste.required' => 'El tipo de ajuste de inventario es obligatorio.',
            'tipo_ajuste.in'       => 'El tipo de ajuste seleccionado no es válido (Debe ser: INCREMENTO, DISMINUCION o REEVALUACION).',
            
            'motivo.required'      => 'Debe especificar el motivo o justificación legal del ajuste de inventario.',
            'motivo.string'        => 'El motivo del ajuste debe ser una cadena de texto válida.',
            'motivo.max'           => 'El motivo del ajuste no debe exceder los 100 caracteres.',
            
            'observaciones.string' => 'Las observaciones del ajuste deben ser una cadena de texto válida.',
            
            'detalles.required'    => 'Debe incluir al menos un artículo para realizar el ajuste de inventario.',
            'detalles.array'       => 'La estructura de los detalles del ajuste debe ser un arreglo válido.',
            'detalles.min'         => 'Debe registrar al menos un detalle de producto en el documento.',
        
            'detalles.*.lote_id.required'           => 'El lote del producto es obligatorio para procesar la transacción.',
            'detalles.*.lote_id.exists'             => 'El lote de inventario especificado no existe en los registros del sistema.',
            
            'detalles.*.cantidad_fisica.required_if' => 'La cantidad física es obligatoria cuando el tipo de ajuste es de INCREMENTO o DISMINUCION.',
            'detalles.*.cantidad_fisica.numeric'     => 'La cantidad física ingresada debe ser un valor numérico válido.',
            'detalles.*.cantidad_fisica.min'         => 'La cantidad física de inventario no puede ser un número negativo.',
            
            'detalles.*.costo_nuevo.required_if'     => 'El nuevo costo unitario es obligatorio cuando se realiza una REEVALUACION de inventario.',
            'detalles.*.costo_nuevo.numeric'         => 'El nuevo costo de reevaluación debe ser un valor numérico válido.',
            'detalles.*.costo_nuevo.min'             => 'El costo nuevo para la reevaluación debe ser un valor decimal mayor a 0.0000.',
        ];

    }
}

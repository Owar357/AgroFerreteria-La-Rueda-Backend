<?php

namespace App\Http\Requests\Venta;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreVentaRequest extends FormRequest
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
            'tipo_pago' => 'required|in:EFECTIVO,TARJETA,TRANSFERENCIA',
            'estado' => 'sometimes|in:ANULADA',
            'gravado' => 'required|numeric|min:0',   
            'exento' => 'required|numeric|min:0',
            'total' => 'required|numeric|min:0',
            'efectivo_recibido' => 'nullable|numeric|min:0',
            'cambio' => 'nullable|numeric|min:0',
            'cliente_id' => 'nullable|exists:clientes,id',
            'apertura_caja_id' => 'required|exists:turnos_caja,id',

            'detalles' => 'required|array|min:1',
            'detalles.*.nombre_producto' => 'required|string',
            'detalles.*.presentacion' => 'required|string',
            'detalles.*.unidad_base' => 'required|string',
            'detalles.*.cantidad' => 'required|numeric|min:0',
            'detalles.*.precio_unitario' => 'required|numeric|min:0',
            'detalles.*.subtotal' => 'required|numeric|min:0',
            'detalles.*.iva_aplicado' => 'required|numeric|min:0',
            'detalles.*.descuento_aplicado' => 'required|numeric|min:0',
            'detalles.*.presentacion_id' => 'required|exists:presentaciones,id',

        ];
    }
}

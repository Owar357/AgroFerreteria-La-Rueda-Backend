<?php

namespace App\Http\Requests\Compra;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreCompraRequest extends FormRequest
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
            // Compra
            'tipo_dte' => 'required|string',
            'numero_documento' => 'nullable|string|max:25',
            'fecha_emision' => 'nullable|date',
            'descuento_global' => 'nullable|numeric|min:0',
            'iva_total' => 'nullable|numeric|min:0',
            'monto_total' => 'nullable|numeric|min:0',
            'estado_pago' => 'required|in:PAGADO,PENDIENTE,ABONADO,VENCIDO',
            'fecha_vencimiento_pago' => 'nullable|date|after_or_equal:fecha_emision',
            'proveedor_id' => 'required|exists:proveedores,id',
            

            // DetalleCompra
            'detalles' => 'required|array|min:1',
            'detalles.*.cantidad_facturada' => 'nullable|numeric|min:0',
            'detalles.*.cantidad_bonificada' => 'nullable|numeric|min:0',
            'detalles.*.precio_unitario_factura' => 'nullable|numeric|min:0',
            'detalles.*.iva_linea' => 'nullable|numeric|min:0',
            'detalles.*.descuento_linea' => 'nullable|numeric|min:0',
            'detalles.*.sub_total' => 'nullable|numeric|min:0',

            // Lote
            'detalles.*.lote.lote_fabricante' => 'nullable|string|max:50',
            'detalles.*.lote.fecha_vencimiento' => 'nullable|date',
            'detalles.*.lote.cantidad_inicial' => 'nullable|numeric|min:0',
            'detalles.*.lote.costo_unitario_compra' => 'nullable|numeric|min:0',
            'detalles.*.lote.porcentaje_descuento' => 'nullable|numeric|min:0',
            'detalles.*.lote.presentacion_id' => 'required|exists:presentaciones,id',
        ];
    }
}

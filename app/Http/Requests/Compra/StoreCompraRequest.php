<?php

namespace App\Http\Requests\Compra;


use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Override;

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
            'numero_documento' => 'nullable|string|min:6|max:31',
            'fecha_emision' => 'nullable|date',
            'descuento_global' => 'nullable|numeric|min:0',
            'iva_total' => 'nullable|numeric|min:0',
            'monto_total' => 'nullable|numeric|min:0',
            'estado_pago' => 'required|in:PAGADO,PENDIENTE,ABONADO,VENCIDO',
            'fecha_vencimiento_pago' => 'nullable|date|after_or_equal:fecha_emision',
            'proveedor_id' => ['required',
            Rule::exists('proveedores', 'id')->where(function ($query){
                $query->where('activo',true);
            }),
            ], 

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

    
    public function messages(): array
    {
        return [
            'tipo_dte.required' => 'El tipo de DTE es obligatorio',
            'tipo_dte.string' => 'El tipo de DTE debe ser un texto válido',
            'numero_documento.min' => 'El número de documento debe tener al menos 6 caracteres',
            'numero_documento.max' => 'El número de documento no puede exceder los 31 caracteres',
            'fecha_emision.date' => 'La fecha de emisión debe ser una fecha válida',
            'descuento_global.numeric' => 'El descuento global debe ser un valor numérico',
            'descuento_global.min' => 'El descuento global no puede ser negativo',
            'iva_total.numeric' => 'El IVA total debe ser un valor numérico',
            'iva_total.min' => 'El IVA total no puede ser negativo',
            'monto_total.numeric' => 'El monto total debe ser un valor numérico',
            'monto_total.min' => 'El monto total no puede ser negativo',
            'estado_pago.required' => 'El estado de pago es obligatorio',
            'estado_pago.in' => 'El estado de pago debe ser PAGADO, PENDIENTE, ABONADO o VENCIDO',
            'fecha_vencimiento_pago.date' => 'La fecha de vencimiento de pago debe ser una fecha válida',
            'fecha_vencimiento_pago.after_or_equal' => 'La fecha de vencimiento de pago no puede ser anterior a la fecha de emisión',
            'proveedor_id.required' => 'El proveedor es obligatorio',
            'proveedor_id.exists' => 'El proveedor seleccionado no está disponible o no existe',

            'detalles.required' => 'Debe agregar al menos un producto a la compra',
            'detalles.min' => 'Debe agregar al menos un producto a la compra',
            'detalles.*.cantidad_facturada.numeric' => 'La cantidad facturada debe ser un valor numérico',
            'detalles.*.cantidad_facturada.min' => 'La cantidad facturada no puede ser negativa',
            'detalles.*.cantidad_bonificada.numeric' => 'La cantidad bonificada debe ser un valor numérico',
            'detalles.*.cantidad_bonificada.min' => 'La cantidad bonificada no puede ser negativa',
            'detalles.*.precio_unitario_factura.numeric' => 'El precio unitario de factura debe ser un valor numérico',
            'detalles.*.precio_unitario_factura.min' => 'El precio unitario de factura no puede ser negativo',
            'detalles.*.iva_linea.numeric' => 'El IVA de línea debe ser un valor numérico',
            'detalles.*.iva_linea.min' => 'El IVA de línea no puede ser negativo',
            'detalles.*.descuento_linea.numeric' => 'El descuento de línea debe ser un valor numérico',
            'detalles.*.descuento_linea.min' => 'El descuento de línea no puede ser negativo',
            'detalles.*.sub_total.numeric' => 'El subtotal debe ser un valor numérico',
            'detalles.*.sub_total.min' => 'El subtotal no puede ser negativo',

            'detalles.*.lote.lote_fabricante.max' => 'El lote del fabricante no puede exceder los 50 caracteres',
            'detalles.*.lote.fecha_vencimiento.date' => 'La fecha de vencimiento del lote debe ser una fecha válida',
            'detalles.*.lote.cantidad_inicial.numeric' => 'La cantidad inicial del lote debe ser un valor numérico',
            'detalles.*.lote.cantidad_inicial.min' => 'La cantidad inicial del lote no puede ser negativa',
            'detalles.*.lote.costo_unitario_compra.numeric' => 'El costo unitario de compra debe ser un valor numérico',
            'detalles.*.lote.costo_unitario_compra.min' => 'El costo unitario de compra no puede ser negativo',
            'detalles.*.lote.porcentaje_descuento.numeric' => 'El porcentaje de descuento debe ser un valor numérico',
            'detalles.*.lote.porcentaje_descuento.min' => 'El porcentaje de descuento no puede ser negativo',
            'detalles.*.lote.presentacion_id.required' => 'La presentación del lote es obligatoria',
            'detalles.*.lote.presentacion_id.exists' => 'La presentación indicada del lote no existe',
        ];

    }
}

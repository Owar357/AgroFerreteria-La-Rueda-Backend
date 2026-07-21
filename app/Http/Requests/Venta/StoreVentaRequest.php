<?php

namespace App\Http\Requests\Venta;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Override;

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
            'gravado' => 'required|numeric|min:0',   
            'exento' => 'required|numeric|min:0',
            'total' => 'required|numeric|min:0',
            'efectivo_recibido' => 'nullable|numeric|min:0',
            'cambio' => 'nullable|numeric|min:0',
            'cliente_id' => 'nullable|exists:clientes,id',
            

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


      public function messages(): array
        {
            return [
            'tipo_pago.required' =>  'El tipo de pago es obligatorio',
            'tipo_pago.in' => 'El tipo de pago debe ser EFECTIVO, TARJETA O TRANSFERENCIA',
            'gravado.required' => 'El monto de gravado es obligatorio' ,
            'exento.required' => 'El monto de exento es obligatorio',
            'total.required' => 'El monto total es obligatorio',
            'total.numeric' => 'El total debe ser un valor numérico',
            'total.min' => 'El total no puede ser negativo',
            'efectivo_recibido.numeric' => 'El efectivo recibido debe ser un valor numérico',
            'efectivo_recibido.min' => 'El efectivo recibido no puede ser negativo',
            'cambio.numeric' => 'El cambio debe ser un valor numérico ',
            'cambio.min' => 'El cambio no puede ser negativo',
            'cliente_id.exists' => 'El cliente seleccionado no existe',
           
            
            'detalles.required' => 'Debe agregar al menos un producto a la venta',
            'detalles.min' => 'Debe agregar al menos un producto a la venta',
            'detalles.*.nombre_producto.required' => 'El nombre del producto es obligatorio',
            'detalles.*.presentacion.required' => 'La presentación es obligatoria',
            'detalles.*.unidad_base.required' => 'La unidad base es obligatoria',
            'detalles.*.cantidad.required' => 'La cantidad es obligatoria',
            'detalles.*.cantidad.min' => 'La cantidad no puede ser negativa',
            'detalles.*.precio_unitario.required' => 'El precio unitario  es obligatorio',
            'detalles.*.subtotal.required'   => 'El subtotal es obligatorio',
            'detalles.*.presentacion_id.exists' => 'La presentación indicada del producto no existe'
            ]; 
        }
}

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
            'tipo_pago'                      => 'required|in:EFECTIVO,TARJETA,TRANSFERENCIA',
            'gravado'                        => 'required|numeric|min:0',   
            'exento'                         => 'required|numeric|min:0',
            'total'                          => 'required|numeric|min:0.01',
            'efectivo_recibido'              => 'required_if:tipo_pago,EFECTIVO|numeric|gte:total',
            'cambio'                         => 'nullable|numeric|min:0',
            'cliente_id'                     => 'nullable|exists:clientes,id',
            
            // Validación del contenedor de artículos
            'detalles'                       => 'required|array|min:1',
            'detalles.*.nombre_producto'     => 'required|string',
            'detalles.*.presentacion'        => 'required|string',
            'detalles.*.unidad_base'         => 'required|string',
            'detalles.*.cantidad'            => 'required|numeric|min:0.0001', 
            'detalles.*.precio_unitario'     => 'required|numeric|min:0',
            'detalles.*.subtotal'            => 'required|numeric|min:0',
            'detalles.*.iva_aplicado'        => 'required|numeric|min:0',
            'detalles.*.descuento_aplicado'  => 'required|numeric|min:0',
            'detalles.*.presentacion_id'     => 'required|exists:presentaciones,id',
            'detalles.*.producto_id'         => 'required|exists:productos,id',
        ];
    }

    /**
     * Get the custom error messages for the defined validation rules.
     */
    #[Override]
    public function messages(): array
    {
        return [
            // Mensajes de la Cabecera de la Venta
            'tipo_pago.required'             => 'El tipo de pago es obligatorio.',
            'tipo_pago.in'                   => 'El tipo de pago debe ser EFECTIVO, TARJETA O TRANSFERENCIA.',
            'gravado.required'               => 'El monto gravado es obligatorio.',
            'gravado.numeric'                => 'El monto gravado debe ser un valor numérico.',
            'exento.required'                => 'El monto exento es obligatorio.',
            'exento.numeric'                 => 'El monto exento debe ser un valor numérico.',
            'total.required'                 => 'El monto total es obligatorio.',
            'total.numeric'                  => 'El total debe ser un valor numérico.',
            'total.min'                      => 'El total debe ser mayor a 0.00.',
            'efectivo_recibido.required_if'  => 'El efectivo recibido es obligatorio cuando el tipo de pago es EFECTIVO.',
            'efectivo_recibido.numeric'      => 'El efectivo recibido debe ser un valor numérico.',
            'efectivo_recibido.min'          => 'El efectivo recibido no puede ser negativo.',
            'efectivo_recibido.gte'          => 'El efectivo recibido no puede ser menor al monto total de la venta.',
            'cambio.numeric'                 => 'El cambio debe ser un valor numérico.',
            'cambio.min'                     => 'El cambio no puede ser negativo.',
            'cliente_id.exists'              => 'El cliente seleccionado no existe en los registros.',
           
          
            'detalles.required'              => 'Debe agregar al menos un producto a la venta.',
            'detalles.min'                   => 'Debe agregar al menos un producto a la venta.',
            'detalles.*.nombre_producto.required' => 'El nombre del producto es obligatorio.',
            'detalles.*.presentacion.required'    => 'La presentación es obligatoria.',
            'detalles.*.unidad_base.required'     => 'La unidad base es obligatoria.',
            
            'detalles.*.cantidad.required'        => 'La cantidad es obligatoria.',
            'detalles.*.cantidad.numeric'         => 'La cantidad debe ser un valor numérico.',
            'detalles.*.cantidad.min'             => 'La cantidad a vender debe ser mayor a 0.00.',
            
            'detalles.*.precio_unitario.required' => 'El precio unitario es obligatorio.',
            'detalles.*.precio_unitario.numeric'  => 'El precio unitario debe ser un valor numérico.',
            
            'detalles.*.subtotal.required'        => 'El subtotal es obligatorio.',
            'detalles.*.subtotal.numeric'         => 'El subtotal debe ser un valor numérico.',
            
            'detalles.*.iva_aplicado.numeric'       => 'El IVA aplicado debe ser un valor numérico.',
            'detalles.*.descuento_aplicado.numeric' => 'El descuento aplicado debe ser un valor numérico.',
            
            'detalles.*.presentacion_id.required' => 'La presentación del producto es obligatoria.',
            'detalles.*.presentacion_id.exists'   => 'La presentación indicada del producto no existe.',
            
            'detalles.*.producto_id.required'     => 'El identificador del producto es requerido.',
            'detalles.*.producto_id.exists'       => 'El producto especificado no existe en el inventario.',    
        ]; 
    }
}

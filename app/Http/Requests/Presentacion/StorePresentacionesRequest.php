<?php

namespace App\Http\Requests\Presentacion;

use App\Models\Presentacion;
use App\Models\Producto;
use App\Models\UnidadMedida;
use Illuminate\Foundation\Http\FormRequest;

class StorePresentacionesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nombre' => 'required|string|max:100',
            'factor_conversion' => 'required|numeric|min:0.0001',
            'precio_venta' => 'required|numeric|min:0',
            'stock_minimo' => 'nullable|numeric|min:0',
            'es_base' => 'nullable|boolean',
            'producto_id' => 'required|exists:productos,id',
            'unidad_medida_id' => 'required|exists:unidad_medidas,id'
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $producto = Producto::with('unidadMedida')->find($this->input('producto_id'));

            if (! $producto) {
                return; 
            }

            $esBase = (bool) $this->input('es_base', false);
            $unidadMedidaId = (int) $this->input('unidad_medida_id');

            $unidadPresentacion = UnidadMedida::find($unidadMedidaId);
            if ($unidadPresentacion && $producto->unidadMedida && $unidadPresentacion->magnitud !== $producto->unidadMedida->magnitud) {
                $validator->errors()->add(
                    'unidad_medida_id',
                    "La unidad de medida no es compatible. Debe usar unidades de {$producto->unidadMedida->magnitud}."
                );
            }

            if ($producto->tipo_producto === 'GRANEL') {

                if ($esBase) {
                    $yaExisteBase = Presentacion::where('producto_id', $producto->id)
                        ->where('es_base', true)
                        ->exists();

                    if ($yaExisteBase) {
                        $validator->errors()->add(
                            'es_base',
                            'Este producto ya tiene una presentación base configurada.'
                        );
                    }

                    if (abs((float) $this->input('factor_conversion') - 1.0) > 0.0001) {
                        $validator->errors()->add(
                            'factor_conversion',
                            'El factor de conversión de la presentación base debe ser exactamente 1.000.'
                        );
                    }

                    if ((float) $this->input('stock_minimo', 0) <= 0) {
                        $validator->errors()->add(
                            'stock_minimo',
                            'La presentación base debe tener un stock mínimo mayor a 0.'
                        );
                    }

                } else {
                    if ((float) $this->input('stock_minimo', 0) > 0) {
                        $validator->errors()->add(
                            'stock_minimo',
                            'El stock mínimo solo puede asignarse a la presentación base.'
                        );
                    }
                }

            } else {
                if (abs((float) $this->input('factor_conversion') - 1.0) > 0.0001) {
                    $validator->errors()->add(
                        'factor_conversion',
                        'Para productos de UNIDAD FIJA, el factor de conversión debe ser exactamente 1.000.'
                    );
                }
            }
        });
    }

    public function messages(): array
    {
        return [
      
            'nombre.required' => 'El nombre de la presentación es obligatorio.',
            'nombre.string' => 'El nombre de la presentación debe ser un texto.',
            'nombre.max' => 'El nombre de la presentación no puede superar los 100 caracteres.',

           
            'factor_conversion.required' => 'El factor de conversión es obligatorio.',
            'factor_conversion.numeric' => 'El factor de conversión debe ser un número.',
            'factor_conversion.min' => 'El factor de conversión no puede ser menor a 0.0001.',

            
            'precio_venta.required' => 'El precio de venta es obligatorio.',
            'precio_venta.numeric' => 'El precio de venta debe ser un número.',
            'precio_venta.min' => 'El precio de venta no puede ser negativo.',

            'stock_minimo.numeric' => 'El stock mínimo debe ser un número.',
            'stock_minimo.min' => 'El stock mínimo no puede ser negativo.',

            
            'producto_id.required' => 'El producto asociado es obligatorio.',
            'producto_id.exists' => 'El producto seleccionado no existe.',

        
            'unidad_medida_id.required' => 'La unidad de medida es obligatoria.',
            'unidad_medida_id.exists' => 'La unidad de medida seleccionada no existe.',
        ];
    }
}
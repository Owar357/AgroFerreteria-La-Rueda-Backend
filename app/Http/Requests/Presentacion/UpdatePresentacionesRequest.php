<?php

namespace App\Http\Requests\Presentacion;

use App\Models\Presentacion;
use App\Models\UnidadMedida;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdatePresentacionesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'nombre' => 'sometimes|string|max:100',
            'factor_conversion' => 'sometimes|numeric|min:0.0001',
            'precio_venta' => 'sometimes|numeric|min:0',
            'stock_minimo' => 'sometimes|numeric|min:0',
            'unidad_medida_id' => 'sometimes|exists:unidad_medidas,id',
        ];
    }

    public function withValidator(Validator $validator)
    {
        $validator->after(function ($validator) {

            $presentacion = Presentacion::with('producto.unidadMedida')->find($this->route('presentacione'));

            if (! $presentacion) {
                return;
            }

            $producto = $presentacion->producto;

            // Si se manda unidad_medida_id, validar que sea de la misma magnitud que el producto
            if ($this->filled('unidad_medida_id')) {
                $unidadNueva = UnidadMedida::find($this->input('unidad_medida_id'));
                if ($unidadNueva && $producto->unidadMedida && $unidadNueva->magnitud !== $producto->unidadMedida->magnitud) {
                    $validator->errors()->add(
                        'unidad_medida_id',
                        "La unidad de medida no es compatible. Debe usar unidades de {$producto->unidadMedida->magnitud}."
                    );
                }
            }

            // Si esta presentación es la base de un producto GRANEL, no se le puede tocar factor_conversion ni ponerle stock_minimo <= 0
            if ($producto->tipo_producto === 'GRANEL' && $presentacion->es_base) {

                if ($this->filled('factor_conversion') && abs((float) $this->input('factor_conversion') - 1.0) > 0.0001) {
                    $validator->errors()->add(
                        'factor_conversion',
                        'La presentación base debe mantener un factor de conversión de exactamente 1.000.'
                    );
                }

                if ($this->filled('stock_minimo') && (float) $this->input('stock_minimo') <= 0) {
                    $validator->errors()->add(
                        'stock_minimo',
                        'La presentación base debe mantener un stock mínimo mayor a 0.'
                    );
                }
            }

            // Si es GRANEL pero NO es la base, no se le puede asignar stock_minimo > 0
            if ($producto->tipo_producto === 'GRANEL' && ! $presentacion->es_base) {
                if ($this->filled('stock_minimo') && (float) $this->input('stock_minimo') > 0) {
                    $validator->errors()->add(
                        'stock_minimo',
                        'El stock mínimo solo puede asignarse a la presentación base.'
                    );
                }
            }

            // UNIDAD FIJA: factor_conversion siempre debe ser 1
            if ($producto->tipo_producto === 'UNIDAD FIJA' && $this->filled('factor_conversion')) {
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
            'nombre.string' => 'El nombre debe ser texto.',
            'nombre.max' => 'El nombre no puede tener más de 100 caracteres.',
            'factor_conversion.numeric' => 'El factor de conversión debe ser un número.',
            'factor_conversion.min' => 'El factor de conversión no puede ser menor a 0.0001.',
            'precio_venta.numeric' => 'El precio de venta debe ser un número.',
            'precio_venta.min' => 'El precio de venta no puede ser negativo.',
            'stock_minimo.numeric' => 'El stock mínimo debe ser un número.',
            'stock_minimo.min' => 'El stock mínimo no puede ser negativo.',
            'unidad_medida_id.exists' => 'La unidad de medida seleccionada no existe.',
        ];
    }
}
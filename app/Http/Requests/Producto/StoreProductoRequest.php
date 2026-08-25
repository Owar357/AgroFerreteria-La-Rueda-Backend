<?php

namespace App\Http\Requests\Producto;

use App\Models\UnidadMedida;
use Illuminate\Foundation\Http\FormRequest;

class StoreProductoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

       public function rules(): array
    {
        return [
            'codigo' => 'required|string|min:2|max:14|unique:productos,codigo|regex:/^[A-Za-z0-9-]+$/',
            'nombre' => 'required|string|max:100',
            'fabricante' => 'nullable|string|max:100',
            'tipo_producto' => 'required|in:UNIDAD FIJA,GRANEL',
            'unidad_medida_id' => 'required|exists:unidad_medidas,id',
            'categoria_id' => 'required|exists:categorias,id',

            'presentaciones' => 'required|array|min:1',
            'presentaciones.*.nombre' => 'required|string|max:150', // ← AHORA ES REQUIRED
            'presentaciones.*.factor_conversion' => 'required|numeric|min:0.0001',
            'presentaciones.*.stock_minimo' => 'nullable|numeric|min:0',
            'presentaciones.*.es_base' => 'boolean',
            'presentaciones.*.precio_venta' => 'required|numeric|min:0',
            'presentaciones.*.unidad_medida_id' => 'required|exists:unidad_medidas,id',

            'presentaciones.*.codigos_barra' => 'nullable|array',
            'presentaciones.*.codigos_barra.*.codigo' => 'required|string|unique:codigos_barras,codigo',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $tipoProducto = $this->input('tipo_producto');
            $presentaciones = collect($this->input('presentaciones', []));
            $unidadBaseId = (int) $this->input('unidad_medida_id');

            
            if ($tipoProducto === 'UNIDAD FIJA') {
                $presentaciones->each(function ($p, $index) use ($validator) {
                    if (abs((float) $p['factor_conversion'] - 1.0) > 0.0001) {
                        $validator->errors()->add(
                            "presentaciones.{$index}.factor_conversion",
                            'Para productos de UNIDAD FIJA, el factor de conversión debe ser exactamente 1.000.'
                        );
                    }
                });
            }

          
            if ($tipoProducto === 'GRANEL') {
                $bases = $presentaciones->where('es_base', true);

                if ($bases->count() !== 1) {
                    $validator->errors()->add(
                        'presentaciones',
                        'Para productos a GRANEL es obligatorio configurar exactamente una presentación como presentación base.'
                    );
                    return;
                }

                $baseIndex = $bases->keys()->first();
                $base = $bases->first();

                
                if (abs((float) $base['factor_conversion'] - 1.0) > 0.0001) {
                    $validator->errors()->add(
                        "presentaciones.{$baseIndex}.factor_conversion",
                        'El factor de conversión de la presentación base debe ser exactamente 1.000.'
                    );
                }

                
                if ((int) $base['unidad_medida_id'] !== $unidadBaseId) {
                    $validator->errors()->add(
                        "presentaciones.{$baseIndex}.unidad_medida_id",
                        'La unidad de medida de la presentación base debe coincidir con la unidad de medida principal del producto.'
                    );
                }

                
                if (!isset($base['stock_minimo']) || (float) $base['stock_minimo'] <= 0) {
                    $validator->errors()->add(
                        "presentaciones.{$baseIndex}.stock_minimo",
                        'La presentación base debe tener un stock mínimo mayor a 0 para generar alertas de inventario.'
                    );
                }
            

                // Derivadas: stock_minimo = 0
                $presentaciones->where('es_base', '!=', true)->each(function ($p, $index) use ($validator) {
                    if (isset($p['stock_minimo']) && (float) $p['stock_minimo'] > 0) {
                        $validator->errors()->add(
                            "presentaciones.{$index}.stock_minimo",
                            'El stock mínimo solo puede asignarse a la presentación base. Las presentaciones secundarias deben tener stock mínimo igual a 0.'
                        );
                    }
                });
            }

            
            $unidadBase = UnidadMedida::find($unidadBaseId);
            if ($unidadBase) {
                $magnitudBase = $unidadBase->magnitud;
                $unidadMedidaIds = $presentaciones->pluck('unidad_medida_id')->unique()->filter();
                $unidadesMedida = UnidadMedida::whereIn('id', $unidadMedidaIds)->get()->keyBy('id');

                $presentaciones->each(function ($p, $index) use ($validator, $magnitudBase, $unidadesMedida) {
                    $unidadPres = $unidadesMedida->get($p['unidad_medida_id'] ?? null);
                    if ($unidadPres && $unidadPres->magnitud !== $magnitudBase) {
                        $validator->errors()->add(
                            "presentaciones.{$index}.unidad_medida_id",
                            "La unidad de medida seleccionada ({$unidadPres->nombre}) no es compatible. Debe usar unidades de {$magnitudBase}."
                        );
                    }
                });
            }
        });
    }

    public function messages(): array
    {
        return [
            'codigo.required' => 'El código del producto es obligatorio.',
            'codigo.string' => 'El código del producto debe ser un texto.',
            'codigo.min' => 'El código debe tener un mínimo de 2 caracteres.',
            'codigo.max' => 'El código no puede superar los 14 caracteres.',
            'codigo.unique' => 'Ya existe un producto con este código.',
            'codigo.regex' => 'El código solo puede contener letras, números y guiones.',

            'nombre.required' => 'El nombre del producto es obligatorio.',
            'nombre.string' => 'El nombre del producto debe ser un texto.',
            'nombre.max' => 'El nombre del producto no puede superar los 100 caracteres.',

            'fabricante.max' => 'El fabricante no puede superar los 100 caracteres.',

            'tipo_producto.required' => 'Debe seleccionar el tipo de producto.',
            'tipo_producto.in' => 'El tipo de producto seleccionado no es válido.',

            'unidad_medida_id.required' => 'Debe seleccionar la unidad base del producto.',
            'unidad_medida_id.exists' => 'La unidad de medida seleccionada no existe.',

            'categoria_id.required' => 'Debe seleccionar una categoría.',
            'categoria_id.exists' => 'La categoría seleccionada no existe.',

            'presentaciones.required' => 'Debe agregar al menos una presentación.',
            'presentaciones.array' => 'Las presentaciones deben enviarse en un formato válido.',
            'presentaciones.min' => 'Debe registrar al menos una presentación.',

            'presentaciones.*.nombre.required' => 'El nombre de la presentación es obligatorio.',
            'presentaciones.*.nombre.string' => 'El nombre de la presentación debe ser un texto.',
            'presentaciones.*.nombre.max' => 'El nombre de la presentación no puede superar los 150 caracteres.',

            'presentaciones.*.factor_conversion.required' => 'Debe ingresar el factor de conversión.',
            'presentaciones.*.factor_conversion.numeric' => 'El factor de conversión debe ser un número.',
            'presentaciones.*.factor_conversion.min' => 'El factor de conversión no puede ser menor que 0.0001.',

            'presentaciones.*.stock_minimo.numeric' => 'El stock mínimo debe ser un número.',
            'presentaciones.*.stock_minimo.min' => 'El stock mínimo no puede ser negativo.',

            'presentaciones.*.precio_venta.required' => 'Debe ingresar el precio de venta.',
            'presentaciones.*.precio_venta.numeric' => 'El precio de venta debe ser un número.',
            'presentaciones.*.precio_venta.min' => 'El precio de venta no puede ser menor que 0.',

            'presentaciones.*.unidad_medida_id.required' => 'Debe seleccionar la unidad de medida de la presentación.',
            'presentaciones.*.unidad_medida_id.exists' => 'La unidad de medida seleccionada no existe.',

            'presentaciones.*.codigos_barra.array' => 'Los códigos de barras deben enviarse en un formato válido.',
            'presentaciones.*.codigos_barra.*.codigo.required' => 'El código de barras es obligatorio cuando se envía.',
            'presentaciones.*.codigos_barra.*.codigo.string' => 'El código de barras debe ser un texto.',
            'presentaciones.*.codigos_barra.*.codigo.unique' => 'El código de barras ya se encuentra registrado.',
        ];
    }
}
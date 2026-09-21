<?php

namespace App\Http\Requests\Kardex;;

use Illuminate\Foundation\Http\FormRequest;
use Override;

class GetKardexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Prepara los datos antes de ejecutar la validación.
     */
    protected function prepareForValidation(): void
    {

        if ($this->route('producto')) {
            $this->merge([
                'producto_id' => $this->route('producto'),
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'producto_id'     => 'required|exists:productos,id',
            'presentacion_id' => 'nullable|exists:presentaciones,id',
            'modo_costeo'     => 'nullable|in:PEPS,CPP',
            'fecha_inicio'    => 'nullable|date',
            'fecha_fin'       => 'nullable|date|after_or_equal:fecha_inicio',
            'per_page'        => 'nullable|integer|min:1|max:100',
        ];
    }

    #[Override]
    public function messages(): array
    {
        return [
            'producto_id.required'     => 'El identificador del producto es obligatorio para consultar el Kardex.',
            'producto_id.exists'       => 'El producto seleccionado no se encuentra registrado en el sistema.',
            'presentacion_id.exists'   => 'La presentación seleccionada no es válida o no existe en la base de datos.',
            'modo_costeo.in'           => 'El método de costeo seleccionado no es válido (Debe ser: PEPS o CPP).',
            'fecha_inicio.date'        => 'La fecha de inicio para el filtro del historial debe tener un formato de fecha válido.',
            'fecha_fin.date'           => 'La fecha de fin para el filtro del historial debe tener un formato de fecha válido.',
            'fecha_fin.after_or_equal' => 'La fecha de fin del reporte no puede ser menor o anterior a la fecha de inicio seleccionada.',
            'per_page.integer'         => 'El número de registros por página debe ser un valor entero.',
            'per_page.min'             => 'El número de registros por página debe ser al menos de 1.',
            'per_page.max'             => 'Por motivos de rendimiento del servidor, no puede solicitar más de 100 registros por página.',
        ];
    }
}

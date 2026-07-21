<?php

namespace App\Http\Requests\Proveedor;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreProveedorRequest extends FormRequest
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
            'nombre' => 'required|string|min:3|max:100|unique:proveedores,nombre',
            'direccion' => 'required|string|min:5',
            'correo' => 'nullable|unique:proveedores,correo|email|regex:/^[a-z0-9_.+\-]+@[a-z0-9\-]+\.[a-z]{2,}$/',
            'telefono' => 'required|string',
            'tipo_persona' => 'required',
            'activo' => 'nullable'
        ];
    }

    function messages(): array {
        return[
            'nombre.required' => 'El nombre es obligatorio',
            'nombre.unique' => 'Este proveedor ya fue registrado.',
            'direccion.required' => 'Por favor ingresar una dirección.',
            'correo.regex' => 'No se permite mayusculas',
            'correo.email' => 'Ingresar el correo con el formato correcto(@gmail.com, @hotmail.com, etc.',
            'correo.unique' => 'Ya existe un usuario con este correo',
            'telefono.required' => 'Ingrese un numero telefonico por favor.',
            'tipo_personna.required' => 'Ingrese el tipo de persona por favor.',

        ];
    }
}

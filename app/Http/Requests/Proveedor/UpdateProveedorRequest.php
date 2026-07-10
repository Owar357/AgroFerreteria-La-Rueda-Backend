<?php

namespace App\Http\Requests\Proveedor;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProveedorRequest extends FormRequest
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
            'nombre' => [
                'sometimes', 'string', 'min:3', 'max:100',
                Rule::unique('proveedores', 'nombre')->ignore($this->route('proveedore')),
            ],
            'correo' => [
                'sometimes', 'email',
                Rule::unique('proveedores', 'correo')->ignore($this->route('proveedore')),
                'regex:/^[a-z0-9_.+\-]+@[a-z0-9\-]+\.[a-z]{2,}$/',
            ],
            'direccion' => 'sometimes|string|max:250',
        ];
    }

    public function messages(): array
    {
        return [
            'nombre.min' => 'El nombre debe tener un mínimo de 3 caracteres',
            'nombre.max' => 'El nombre debe tener un máximo de 100 caracteres',
            'nombre.unique' => 'El nombre de ese proveedor ya existe',
            'correo.regex' => 'No se permite mayúsculas',
            'correo.unique' => 'El correo ingresado ya está asociado',
            'correo.email' => 'Ingresar el correo con el formato correcto(@gmail.com, @hotmail.com, etc.)',
            'direccion.max' => 'La dirección debe tener un máximo de 250 caracteres',

        ];
    }
}

<?php

namespace App\Http\Requests\Cliente;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreClienteRequest extends FormRequest
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
        $rules = [
            'tipo_persona' => 'required|in:JURIDICA,NATURAL',
            'tipo_documento_receptor' => 'nullable|in:13,36,02,03',
            'numero_documento' => ['nullable','string', 'max:20',  'required_with:tipo_documento_receptor',
                Rule::unique('clientes')->where(fn ($q) 
                => $q->where('tipo_documento_receptor', $this->tipo_documento_receptor))],
            'telefono' => 'nullable|string|max:20',
            'correo' => 'nullable|email|max:150',
        ];

        if ($this->tipo_persona == 'NATURAL') {
            $rules['nombre'] = 'required|string|max:250';
            $rules['razon_social'] = 'prohibited';
        } else {

            $rules['razon_social'] = 'required|string|max:250';
            $rules['nombre'] = 'prohibited';
            $rules['nrc'] = 'required|string|max:15';
            $rules['giro_actividad'] = 'required|string|max:250';

        }
        return $rules;
    }

    public function messages(): array
{
    return [
        'numero_documento.required_with' => 'El número de documento es obligatorio cuando se especifica el tipo de documento',
        'nombre.prohibited' => 'El campo nombre no debe estar presente para personas jurídicas',
        'razon_social.prohibited' => 'El campo razón social no debe estar presente para personas naturales',
    ];
}
}

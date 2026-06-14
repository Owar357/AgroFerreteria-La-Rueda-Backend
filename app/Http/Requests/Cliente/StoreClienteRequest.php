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
            'numero_documento' => ['nullable', 'string', 'max:20',  'required_with:tipo_documento_receptor',
                Rule::unique('clientes')->where(fn ($q) => $q->where('tipo_documento_receptor', $this->tipo_documento_receptor))],
            'correo' => 'nullable|email|max:150',
            'cod_departamento' => 'nullable|string|size:2',
            'cod_municipio' => 'nullable|string|size:4',
            'complemento' => 'nullable|string|max:250',
        ];

        if ($this->input('tipo_persona') == 'NATURAL') {
            $rules['nombre'] = 'required|string|max:250';
            $rules['razon_social'] = 'prohibited';
            $rules['nrc'] = 'prohibited';
            $rules['giro_actividad'] = 'prohibited';
        } else {
            $rules['nombre'] = 'prohibited';
            $rules['razon_social'] = 'required|string|max:250';
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
            'nombre.required' => 'El nombre es obligatorio para personas naturales',
            'razon_social.required' => 'La razón social es obligatoria para personas jurídicas',
        ];
    }
}

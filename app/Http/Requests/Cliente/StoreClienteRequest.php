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
        'tipo_persona.required' => 'Debe indicar el tipo de persona',
        'tipo_persona.in' => 'El tipo de persona debe ser JURIDICA o NATURAL',
        'tipo_documento_receptor.in' => 'El tipo de documento debe ser 13, 36, 02 o 03',
        'numero_documento.max' => 'El número de documento no puede exceder los 20 caracteres',
        'numero_documento.required_with' => 'El número de documento es obligatorio cuando se especifica el tipo de documento',
        'numero_documento.unique' => 'Ya existe un cliente con este número de documento',
        'correo.email' => 'El correo debe tener un formato válido',
        'correo.max' => 'El correo no puede exceder los 150 caracteres',
        'cod_departamento.size' => 'El código de departamento debe tener exactamente 2 caracteres',
        'cod_municipio.size' => 'El código de municipio debe tener exactamente 4 caracteres',
        'complemento.max' => 'El complemento no puede exceder los 250 caracteres',
        'nombre.required' => 'El nombre es obligatorio para personas naturales',
        'nombre.prohibited' => 'El campo nombre no debe estar presente para personas jurídicas',
        'nombre.max' => 'El nombre no puede exceder los 250 caracteres',
        'razon_social.required' => 'La razón social es obligatoria para personas jurídicas',
        'razon_social.prohibited' => 'El campo razón social no debe estar presente para personas naturales',
        'razon_social.max' => 'La razón social no puede exceder los 250 caracteres',
        'nrc.required' => 'El NRC es obligatorio para personas jurídicas',
        'nrc.prohibited' => 'El campo NRC no debe estar presente para personas naturales',
        'nrc.max' => 'El NRC no puede exceder los 15 caracteres',
        'giro_actividad.required' => 'El giro de actividad es obligatorio para personas jurídicas',
        'giro_actividad.prohibited' => 'El campo giro de actividad no debe estar presente para personas naturales',
        'giro_actividad.max' => 'El giro de actividad no puede exceder los 250 caracteres',
        ];
    }
}

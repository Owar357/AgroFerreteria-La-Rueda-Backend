<?php

namespace App\Http\Requests\Cliente;

use Closure;
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
     * Limpia los datos ANTES de validar. Lo que se valida (y lo que luego devuelve
     * $request->validated() al controlador) ya es el valor limpio:
     *  - DUI, NIT, carnet y NRC: sin guiones ni espacios
     *  - Razón social y giro: mayúsculas y sin espacios repetidos
     */
    protected function prepareForValidation(): void
    {
        $datos = [];

        if ($this->has('nombre')) {
            $datos['nombre'] = $this->limpiarTexto($this->input('nombre'));
        }

        if ($this->has('razon_social')) {
            $datos['razon_social'] = $this->limpiarTexto($this->input('razon_social'), true);
        }

        if ($this->has('giro_actividad')) {
            $datos['giro_actividad'] = $this->limpiarTexto($this->input('giro_actividad'), true);
        }

        if ($this->has('complemento')) {
            $datos['complemento'] = $this->limpiarTexto($this->input('complemento'));
        }

        if ($this->has('correo') && is_string($this->input('correo'))) {
            $datos['correo'] = trim($this->input('correo'));
        }
        if ($this->has('telefono') && is_string($this->input('telefono'))) {
            $datos['telefono'] = trim($this->input('telefono'));
        }

        if ($this->has('nrc') && is_string($this->input('nrc'))) {
            $datos['nrc'] = preg_replace('/[\s-]/', '', $this->input('nrc'));
        }

        if ($this->has('numero_documento') && is_string($this->input('numero_documento'))) {
            $datos['numero_documento'] = $this->normalizarDocumento(
                $this->input('tipo_documento_receptor'),
                $this->input('numero_documento')
            );
        }

        $this->merge($datos);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $esNatural = $this->input('tipo_persona') === 'NATURAL';
        $tipoDocumento = $this->input('tipo_documento_receptor');

        $rules = [
            'tipo_persona' => ['required', 'in:JURIDICA,NATURAL'],
            // La persona jurídica solo puede identificarse con NIT (36)
            'tipo_documento_receptor' => [
                'required',
                Rule::in($esNatural ? ['13', '36', '02', '03'] : ['36']),
            ],
            'numero_documento' => [
                'bail',
                'required',
                'string',
                fn ($attribute, $value, $fail) => $this->validarNumeroDocumento(
                    is_string($tipoDocumento) ? $tipoDocumento : '',
                    (string) $value,
                    $fail
                ),
                Rule::unique('clientes')->where(
                    fn ($q) => $q->where('tipo_documento_receptor', $tipoDocumento)
                ),
            ],
            'telefono' => [
            'required',
            'string',
            'max:20',
            'regex:/^[0-9+\-\s()]+$/',
            ],

            'correo' => [
                'required',
                'string',
                'max:150',
                'regex:/^[A-Za-z0-9]+([._-][A-Za-z0-9]+)*@[A-Za-z0-9]+(-[A-Za-z0-9]+)*(\.[A-Za-z]{2,3})?\.[A-Za-z]{2,}$/',
            ],
            'cod_departamento' => ['required', 'string', 'size:2'],
            'cod_municipio' => ['required', 'string', 'size:4'],
            'complemento' => [
                'bail', 'required', 'string', 'min:5', 'max:250',
                function ($attribute, $value, $fail) {
                    if (preg_match_all('/\pL/u', (string) $value) < 3) {
                        $fail('La dirección debe incluir al menos 3 letras (calle, colonia, referencia...).');
                    }
                },
            ],
        ];

        if ($esNatural) {
            $rules['nombre'] = ['required', 'string', 'max:250', 'regex:/^[\pL ]+$/u'];
            $rules['razon_social'] = ['prohibited'];
            $rules['nrc'] = ['prohibited'];
            $rules['giro_actividad'] = ['prohibited'];
        } else {
            $rules['nombre'] = ['prohibited'];
            $rules['razon_social'] = [
                'bail', 'required', 'string', 'min:3', 'max:200',
                'regex:/^[A-ZÁÉÍÓÚÜÑ0-9 .,&\-]+$/u',
                fn ($attribute, $value, $fail) => $this->validarTextoComercial((string) $value, 3, false, $fail),
            ];
            $rules['giro_actividad'] = [
                'bail', 'required', 'string', 'min:5', 'max:200',
                'regex:/^[A-ZÁÉÍÓÚÜÑ0-9 .,\-]+$/u',
                fn ($attribute, $value, $fail) => $this->validarTextoComercial((string) $value, 5, true, $fail),
            ];
            // 6 a 8 dígitos, ya sin guion (el guion es solo visual en el formulario)
            $rules['nrc'] = [
                'bail', 'required', 'string', 'regex:/^\d{6,8}$/',
                function ($attribute, $value, $fail) {
                    if (preg_match('/^(\d)\1+$/', (string) $value)) {
                        $fail('El NRC no es válido: no puede repetir un mismo dígito.');
                    }
                },
            ];
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'tipo_persona.required' => 'Debe indicar el tipo de persona',
            'tipo_persona.in' => 'El tipo de persona debe ser JURIDICA o NATURAL',

            'tipo_documento_receptor.required' => 'El tipo de documento es obligatorio.',
            'tipo_documento_receptor.in' => 'El tipo de documento no es válido para este tipo de persona.',

            'numero_documento.required' => 'El número de documento es obligatorio.',
            'numero_documento.unique' => 'Ya existe un cliente con este número de documento',

            'correo.required' => 'El correo es obligatorio.',
            'correo.max' => 'El correo no puede exceder los 150 caracteres',
            'correo.regex' => 'Correo inválido. Solo se permiten letras, números y los símbolos . _ -',

            'telefono.required' => 'El teléfono es obligatorio.',
            'telefono.max' => 'El teléfono no puede exceder los 20 caracteres.',
            'telefono.regex' => 'El teléfono contiene caracteres no válidos.',

            
            'cod_departamento.required' => 'El departamento es obligatorio.',
            'cod_departamento.size' => 'El código de departamento debe tener exactamente 2 caracteres',
            'cod_municipio.required' => 'El municipio es obligatorio.',
            'cod_municipio.size' => 'El código de municipio debe tener exactamente 4 caracteres',
            'complemento.required' => 'La dirección complementaria es obligatoria.',
            'complemento.min' => 'La dirección debe tener al menos 5 caracteres.',
            'complemento.max' => 'El complemento no puede exceder los 250 caracteres',

            'nombre.required' => 'El nombre es obligatorio para personas naturales',
            'nombre.regex' => 'El nombre solo puede contener letras y espacios.',
            'nombre.prohibited' => 'El campo nombre no debe estar presente para personas jurídicas',
            'nombre.max' => 'El nombre no puede exceder los 250 caracteres',

            'razon_social.required' => 'La razón social es obligatoria para personas jurídicas',
            'razon_social.min' => 'La razón social debe tener al menos 3 caracteres.',
            'razon_social.max' => 'La razón social no puede exceder los 200 caracteres',
            'razon_social.regex' => 'La razón social solo puede contener letras, números y los símbolos . , - &',
            'razon_social.prohibited' => 'El campo razón social no debe estar presente para personas naturales',

            'nrc.required' => 'El NRC es obligatorio para personas jurídicas',
            'nrc.regex' => 'El NRC debe tener entre 6 y 8 dígitos (ej: 123456-7).',
            'nrc.prohibited' => 'El campo NRC no debe estar presente para personas naturales',

            'giro_actividad.required' => 'El giro de actividad es obligatorio para personas jurídicas',
            'giro_actividad.min' => 'El giro de actividad debe tener al menos 5 caracteres.',
            'giro_actividad.max' => 'El giro de actividad no puede exceder los 200 caracteres',
            'giro_actividad.regex' => 'El giro de actividad solo puede contener letras, números y los símbolos . , -',
            'giro_actividad.prohibited' => 'El campo giro de actividad no debe estar presente para personas naturales',
        ];
    }

    /* ------------------------------------------------------------------ */
    /* Sanitización                                                        */
    /* ------------------------------------------------------------------ */

    /** Quita espacios al inicio/final y reduce los espacios repetidos a uno solo. */
    private function limpiarTexto(mixed $valor, bool $mayusculas = false): mixed
    {
        if (! is_string($valor)) {
            return $valor;
        }

        $valor = trim(preg_replace('/\s+/u', ' ', $valor) ?? '');

        return $mayusculas ? mb_strtoupper($valor, 'UTF-8') : $valor;
    }

    /**
     * DUI (13), NIT (36) y carnet de residente (03): sin guiones ni espacios.
     * Pasaporte (02): solo mayúsculas; los guiones, puntos o espacios NO se quitan
     * para que la validación los rechace.
     */
    private function normalizarDocumento(mixed $tipo, string $numero): string
    {
        $tipo = is_string($tipo) ? $tipo : '';

        return match ($tipo) {
            '13', '36', '03' => strtoupper(preg_replace('/[\s-]/', '', $numero)),
            default => strtoupper(trim($numero)),
        };
    }

    /* ------------------------------------------------------------------ */
    /* Validación del número de documento según su tipo                    */
    /* ------------------------------------------------------------------ */

    private function validarNumeroDocumento(string $tipo, string $numero, Closure $fail): void
    {
        switch ($tipo) {
            case '13': // DUI
                if (! preg_match('/^\d{9}$/', $numero)) {
                    $fail('El DUI debe tener 9 dígitos (formato xxxxxxxx-x).');
                } elseif (! $this->validarDui($numero)) {
                    $fail('El número de DUI no es válido.');
                }
                break;

            case '36': // NIT
                if (! preg_match('/^\d{14}$/', $numero)) {
                    $fail('El NIT debe tener 14 dígitos (formato xxxx-xxxxxx-xxx-x).');
                } elseif (preg_match('/^(\d)\1+$/', $numero)) {
                    $fail('El NIT no es válido: no puede repetir un mismo dígito.');
                }
                break;

            case '02': // Pasaporte
                if (! preg_match('/^(?=.*[A-Z])(?=.*\d)[A-Z0-9]{6,15}$/', $numero)) {
                    $fail('El pasaporte debe tener de 6 a 15 caracteres, con letras y números, sin guiones, puntos ni espacios.');
                }
                break;

            case '03': // Carnet de residente
                if (! preg_match('/^[A-Z0-9]{5,15}$/', $numero)) {
                    $fail('El carnet de residente debe tener de 5 a 15 caracteres alfanuméricos.');
                }
                break;
        }
    }

    /**
     * Reglas comunes de razón social y giro. Evitan textos sin sentido como "&&&&&" o "AAAAA".
     * El valor llega ya en mayúsculas y sin espacios repetidos (prepareForValidation).
     */
    private function validarTextoComercial(string $valor, int $minLetras, bool $empiezaConLetra, Closure $fail): void
    {
        $inicio = $empiezaConLetra ? '/^[A-ZÁÉÍÓÚÜÑ]/u' : '/^[A-ZÁÉÍÓÚÜÑ0-9]/u';

        if (! preg_match($inicio, $valor)) {
            $fail($empiezaConLetra ? 'Debe comenzar con una letra.' : 'Debe comenzar con una letra o un número.');
            return;
        }

        if (preg_match_all('/[A-ZÁÉÍÓÚÜÑ]/u', $valor) < $minLetras) {
            $fail("Debe contener al menos {$minLetras} letras.");
            return;
        }

        if (preg_match('/([.,&-])\1/u', $valor)) {
            $fail('No repitas símbolos seguidos (por ejemplo && o ..).');
            return;
        }

        if (preg_match('/([A-ZÁÉÍÓÚÜÑ])\1{3,}/u', $valor)) {
            $fail('No repitas la misma letra más de 3 veces seguidas.');
        }
    }

    /**
     * Valida el dígito verificador del DUI. Recibe los 9 dígitos sin guion.
     */
    private function validarDui(string $dui): bool
    {
        if (! preg_match('/^\d{9}$/', $dui)) {
            return false;
        }

        $digitos = str_split($dui);
        $factores = [9, 8, 7, 6, 5, 4, 3, 2];
        $suma = 0;

        for ($i = 0; $i < 8; $i++) {
            $suma += (int) $digitos[$i] * $factores[$i];
        }

        $residuo = $suma % 10;
        $digitoVerificador = (10 - $residuo) % 10; // Si el residuo es 0, el dígito será 0

        return (int) $digitos[8] === $digitoVerificador;
    }
}
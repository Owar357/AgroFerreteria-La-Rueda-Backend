<?php

namespace App\Http\Requests\Presentacion;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Override;

class StorePresentacionesRequest extends FormRequest
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
            "nombre" => "required|string|max:100",
            "factor_conversion" => "required|numeric|min:0",
            "precio_venta" => "required|numeric|min:0",
            "producto_id" => "required|exists:productos,id"
        ];
    }

    
    public function messages() : array
    {
        return [
          "nombre.required" => "El nombre de la presentación es requerido",
          "nombre.max" => "El máximo de carácteres permitidos son 100",   
          "factor_conversion.required" => "El factor de conversión es requerido",
          "factor_conversion.numeric" => "No puede haber letras en el factor de conversión",
          "factor_conversion.min" => "El factor de conversión no puede ser menor a 0",
          "precio_venta.required" => "El precio de venta es requerido",
          "precio_venta.numeric" => "El precio de venta solo puede contener valores numéricos",
          "precio_venta.min" => "El precio de venta no puede ser negativo",
          "producto_id.required" => "El producto asociado es requerido",
          "producto_id.exists" => "El producto no existe"
        ];
    }
}   

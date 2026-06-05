<?php

namespace App\Http\Requests\Presentaciones;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

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
}

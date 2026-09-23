<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Configuracion extends Model
{
    protected $table = 'configuraciones';
 
    protected $fillable = [
        'clave',
        'valor',
        'descripcion',
    ];
 
    /** Clave del fondo fijo de operación de la gaveta. */
    public const FONDO_FIJO_CAJA = 'fondo_fijo_caja';
 
    /** Valor por defecto en código si la clave no existe o es inválida. */
    public const FONDO_FIJO_DEFECTO = '75.00';
 
    /**
     * Devuelve el valor de una configuración, o $defecto si no existe.
     */
    public static function obtener(string $clave, ?string $defecto = null): ?string
    {
        return static::query()->where('clave', $clave)->value('valor') ?? $defecto;
    }
}

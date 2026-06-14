<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class Cliente extends Model
{
    use HasFactory;

    protected $fillable = [
        'tipo_persona',
        'nombre',
        'razon_social',
        'tipo_documento_receptor',
        'numero_documento',
        'nrc',
        'cod_actividad',
        'giro_actividad',
        'cod_departamento',
        'cod_municipio',
        'complemento',
        'correo',
        'activo',
        'registrado_por',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    public function registradoPor()
    {
        return $this->belongsTo(User::class, 'registrado_por');
    }

    public function ventas()
    {
        return $this->hasMany(Venta::class);
    }
}

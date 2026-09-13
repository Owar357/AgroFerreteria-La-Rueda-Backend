<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class Categoria extends Model
{
    protected $fillable = [
        'nombre',
        'activo',
        'porcentaje_ganancia_minimo',
        'creado_por',
    ];

    protected $casts = [
        'activo' => 'boolean',
        'porcentaje_ganancia_minimo' => 'float',
    ];

    public function creadoPor()
    {
        return $this->belongsTo(User::class, 'creado_por');
    }

    public function productos()
    {
        return $this->hasMany(Producto::class);
    }
}

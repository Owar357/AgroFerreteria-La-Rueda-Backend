<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class Producto extends Model
{
    protected $fillable = [
        'codigo',
        'nombre',
        'fabricante',
        'tipo_producto',
        'unidad_base',
        'aplica_iva',
        'categoria_id',
        'registrado_por',
        'stock_minimo',
    ];

    protected $casts = [
        'aplica_iva' => 'boolean',
        'stock_minimo' => 'decimal:3'
    ];

    public function categoria()
    {
        return $this->belongsTo(Categoria::class);
    }

    public function registradoPor()
    {
        return $this->belongsTo(User::class, 'registrado_por');
    }

    public function presentaciones()
    {
        return $this->hasMany(Presentacion::class);
    }
}



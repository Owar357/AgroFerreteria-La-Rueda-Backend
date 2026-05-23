<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\CodigoBarra;

class Presentacion extends Model
{
    protected $table = 'presentaciones';

    protected $fillable = [
        'nombre',
        'factor_conversion',
        'precio_venta',
        'activo',
        'producto_id',
    ];

    protected $casts = [
        'activo' => 'boolean',
        'factor_conversion' => 'decimal:3',
        'precio_venta' => 'decimal:4',
    ];

    public function producto()
    {
        return $this->belongsTo(Producto::class);
    }

    public function codigosBarras()
    {
        return $this->hasMany(CodigoBarra::class);
    }
}

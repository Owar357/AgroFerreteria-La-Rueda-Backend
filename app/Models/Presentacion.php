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
        'stock_minimo',
        'es_base',
        'activo',
        'producto_id',
        'unidad_medida_id',
    ];

    protected $casts = [
        'activo' => 'boolean',
        'es_base' => 'boolean',
        'factor_conversion' => 'decimal:3',
        'precio_venta' => 'decimal:4',
        'stock_minimo' => 'decimal:4',
    ];

    public function producto()
    {
        return $this->belongsTo(Producto::class);
    }

    public function codigosBarras()
    {
        return $this->hasMany(CodigoBarra::class);
    }

     public function unidadMedida()
    {
        return $this->belongsTo(UnidadMedida::class, 'unidad_medida_id');
    }

    public function lotes()
    {
        return $this->hasMany(Lote::class);
    }
}

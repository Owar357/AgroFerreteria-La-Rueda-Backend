<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Lote extends Model
{
    protected $fillable = [
        'lote_interno',
        'lote_fabricante',
        'fecha_vencimiento',
        'cantidad_inicial',
        'cantidad_actual',
        'costo_unitario_compra',
        'porcentaje_descuento',
        'estado',
        'presentacion_id',
    ];

    protected $casts = [
        'fecha_vencimiento'     => 'date',
        'cantidad_inicial'      => 'decimal:3',
        'cantidad_actual'       => 'decimal:3',
        'costo_unitario_compra' => 'decimal:4',
        'porcentaje_descuento'  => 'decimal:2',
    ];

    
    public function presentacion()
    {
        return $this->belongsTo(Presentacion::class);
    }

    public function detallesCompra()
    {
        return $this->hasMany(DetalleCompra::class);
    }
}

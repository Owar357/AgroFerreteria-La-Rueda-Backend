<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DetalleVenta extends Model
{
    protected $table = 'detalles_venta';

    protected $fillable = [
        'numero_lote',
        'nombre_producto',
        'presentacion',
        'cantidad',
        'precio_unitario',
        'subtotal',
        'iva_aplicado',
        'descuento_aplicado',
        'lote_id',
        'venta_id',
    ];

    protected $casts = [
        'cantidad' => 'decimal:4',
        'precio_unitario' => 'decimal:4',
        'subtotal' => 'decimal:2',
        'iva_aplicado' => 'decimal:2',
        'descuento_aplicado' => 'decimal:2',
    ];

    public function lote()
    {
        return $this->belongsTo(Lote::class);
    }

    public function venta()
    {
        return $this->belongsTo(Venta::class);
    }

}

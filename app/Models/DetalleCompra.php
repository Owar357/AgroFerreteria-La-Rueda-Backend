<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DetalleCompra extends Model
{
    protected $table = 'detalles_compra';

    protected $fillable = [
        'cantidad_facturada',
        'cantidad_bonificada',
        'precio_unitario_factura',
        'iva_linea',
        'descuento_linea',
        'sub_total',
        'compra_id',
        'lote_id',
    ];

    protected $casts = [
        'cantidad_facturada' => 'decimal:4',
        'cantidad_bonificada' => 'decimal:4',
        'precio_unitario_factura' => 'decimal:2',
        'iva_linea' => 'decimal:2',
        'descuento_linea' => 'decimal:2',
    ];

    public function compra()
    {
        return $this->belongsTo(Compra::class);
    }

    public function lote()
    {
        return $this->belongsTo(Lote::class);
    }
}

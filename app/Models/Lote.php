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
        'producto_id',
    ];

        protected $casts = [
            'fecha_vencimiento'     => 'date',
            'cantidad_inicial'      => 'decimal:4',
            'cantidad_actual'       => 'decimal:4',
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

        public function producto(){
            return $this->belongsTo(Producto::class);
        }
}

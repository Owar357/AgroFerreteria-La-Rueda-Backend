<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoteDetalleVenta extends Model
{
    
    protected $table = 'lote_detalles_venta';


    protected $fillable = [
        'detalle_venta_id',
        'lote_id',
        'cantidad_tomada',
        'numero_lote',
    ];

   
    public function detalleVenta()
    {
        return $this->belongsTo(DetalleVenta::class,);
    }


    public function lote()
    {
        return $this->belongsTo(Lote::class);
    }
}

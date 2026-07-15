<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MovimientoExternoCaja extends Model
{
    protected $table = 'movimientos_externo_cajas';

    protected $fillable = [
        'es_anulado',
        'tipo_movimiento',
        'monto',
        'motivo',
        'apertura_venta_id',  
        'user_id'
    ];

    protected $casts = [
        'es_anulado' => 'boolean',
        'monto' => 'decimal:2',
    ];

  public function aperturaVenta(){
    return $this->belongsTo(AperturaVenta::class,'apertura_venta_id');
  }

  public function user(){
    return $this->belongsTo(User::class,'user_id'); 
  }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AperturaVenta extends Model
{
    protected $table = 'apertura_ventas';

    protected $fillable = [
        'fecha_hora_apertura',
        'fecha_hora_cierre',
        'monto_inicial',
        'monto_esperado',
        'monto_contado',
        'diferencia',
        'estado_arqueo',
        'estado',
        'apertura_caja_id',
        'cajero_id',
        'cerrada_por',
    ];

    protected $casts = [
        'fecha_hora_apertura' => 'datetime',
        'fecha_hora_cierre' => 'datetime',
        'monto_inicial' => 'decimal:2',
        'monto_esperado' => 'decimal:2',
        'monto_contado' => 'decimal:2',
        'diferencia' => 'decimal:2',
    ];

    public function aperturaCaja(){
        return  $this->belongsTo(AperturaCaja::class,'apertura_caja_id');
    }

    public function cajero(){
        return $this->belongsTo(User::class,'cajero_id');
    }

     public function cerradoPor(){
        return $this->belongsTo(User::class,'cerrada_por');
    }

    public function movimientoExternoCaja(){
        return $this->hasMany(MovimientoExternoCaja::class);
    }
}

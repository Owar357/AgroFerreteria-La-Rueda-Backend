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
        'justificacion',
        'estado_arqueo',
        'estado',
        'apertura_caja_id',
        'cajero_id',
        'cerrada_por',
        'fondo_fijo_referencia',
        'justificacion_apertura',
        'retiro_efectivo',
        'fondo_siguiente_turno',
        'denominaciones_apertura',
        'denominaciones_cierre',
    ];

    protected $casts = [
        'fecha_hora_apertura' => 'datetime',
        'fecha_hora_cierre' => 'datetime',
        'monto_inicial' => 'decimal:2',
        'monto_esperado' => 'decimal:2',
        'monto_contado' => 'decimal:2',
        'diferencia' => 'decimal:2',
        'fondo_fijo_referencia' => 'decimal:2',
        'retiro_efectivo' => 'decimal:2',
        'fondo_siguiente_turno' => 'decimal:2',
        'denominaciones_apertura' => 'array',
        'denominaciones_cierre' => 'array',
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

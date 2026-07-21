<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TurnoCaja extends Model
{
    protected $table = 'turnos_caja';

    protected $fillable = [
        'fecha_hora_apertura',
        'fecha_hora_cierre',
        'monto_inicial',
        'monto_esperado',
        'monto_real_caja',
        'diferencia',
        'estado',
        'abierta_por',
        'cerrada_por',
    ];

    protected $casts = [
        'fecha_hora_apertura' => 'datetime',
        'fecha_hora_cierre' => 'datetime',
        'monto_inicial' => 'decimal:2',
        'monto_esperado' => 'decimal:2',
        'monto_real_caja' => 'decimal:2',
        'diferencia' => 'decimal:2',
    ];

    public function abiertaPor()
    {
        return $this->belongsTo(User::class, 'abierta_por');
    }

    public function cerradaPor()
    {
        return $this->belongsTo(User::class, 'cerrada_por');
    }

    public function ventas()
    {
        return $this->hasMany(Venta::class, 'apertura_caja_id');
    }

    public function movimientosCaja()
    {
        return $this->hasMany(MovimientoCaja::class, 'turno_caja_id');
    }
}

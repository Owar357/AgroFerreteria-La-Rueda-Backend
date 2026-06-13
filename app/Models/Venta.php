<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Venta extends Model
{
    protected $fillable = [
        'numero_factura',
        'tipo_pago',
        'estado',
        'gravado',
        'exento',
        'iva',
        'total',
        'efectivo_recibido',
        'cambio',
        'fecha_hora_anulacion',
        'cliente_id',
        'vendido_por',
        'anulado_por',
        'apertura_caja_id',
    ];

    protected $casts = [
        'gravado' => 'decimal:2',
        'exento' => 'decimal:2',
        'iva' => 'decimal:2',
        'total' => 'decimal:2',
        'efectivo_recibido' => 'decimal:2',
        'cambio' => 'decimal:2',
        'fecha_hora_anulacion' => 'datetime',
    ];

    public function cliente()
    {
        return $this->belongsTo(Cliente::class);
    }

    public function vendidoPor()
    {
        return $this->belongsTo(User::class, 'vendido_por');
    }

    public function anuladoPor()
    {
        return $this->belongsTo(User::class, 'anulado_por');
    }

    public function aperturaCaja()
    {
        return $this->belongsTo(TurnoCaja::class, 'apertura_caja_id');
    }

    public function detallesVenta()
    {
        return $this->hasMany(DetalleVenta::class);
    }
}

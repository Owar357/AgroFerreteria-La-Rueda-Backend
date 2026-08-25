<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Alerta extends Model
{
    protected $fillable = [
        'tipo',
        'mensaje',
        'estado',
        'prioridad',
        'leida',
        'leida_por',
        'ultima_notificacion_at',
        'lote_id',
        'presentacion_id',
        'compra_id',
    ];

    protected $casts = [
        'leida' => 'boolean',
        'ultima_notificacion_at' => 'datetime',
    ];

    public function leidaPor()
    {
        return $this->belongsTo(User::class, 'leida_por');
    }

    public function lote()
    {
        return $this->belongsTo(Lote::class);
    }

    public function presentacion()   
    {
        return $this->belongsTo(Presentacion::class);
    }


    public function compra()
    {
        return $this->belongsTo(Compra::class);
    }
}

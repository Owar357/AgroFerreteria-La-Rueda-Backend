<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Alerta extends Model
{
    protected $fillable = [
        'tipo',
        'prioridad',
        'leida',
        'leida_por',
        'lote_id',
        'presentacion_id',
        'compra_id',
    ];

    protected $casts = [
        'leida' => 'boolean',
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

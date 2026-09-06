<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DetalleAjusteInventario extends Model
{
    protected $table = 'detalles_ajuste_inventario';

    protected $fillable = [
        'cantidad_sistema',
        'cantidad_fisica',
        'diferencia',
        'costo_anterior',
        'costo_nuevo',
        'monto_impacto',
        'ajuste_inventario_id',
        'lote_id',
    ];

    protected $casts = [
        'cantidad_sistema' => 'decimal:4',
        'cantidad_fisica' => 'decimal:4',
        'diferencia' => 'decimal:4',
        'costo_anterior' => 'decimal:4',
        'costo_nuevo' => 'decimal:4',
        'monto_impacto' => 'decimal:2',
    ];

    public function ajustesInventario(){
        return $this->belongsTo(AjusteInventario::class);
    }

    public function lotes(){
        return $this->hasMany(lote::class);
    }
}

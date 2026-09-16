<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AjusteInventario extends Model
{
    protected $table = 'ajustes_inventario';

    protected $fillable = [
        'numero_ajuste',
        'tipo_ajuste',
        'motivo',
        'observaciones',
        'usuario_id',
    ];

    public function Usuario()
    {
        return $this->belongsTo(User::class);
    }

    public function detalles()
    {
        return $this->hasMany(DetalleAjusteInventario::class);
    }

}

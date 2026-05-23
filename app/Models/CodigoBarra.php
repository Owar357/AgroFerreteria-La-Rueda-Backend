<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Presentacion;

class CodigoBarra extends Model
{
    protected $table = 'codigos_barras';

    protected $fillable = [
        'codigo',
        'activo',
        'presentacion_id',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    public function presentacion()
    {
        return $this->belongsTo(Presentacion::class);
    }
}

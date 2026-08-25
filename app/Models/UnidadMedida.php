<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UnidadMedida extends Model
{
    protected $table = 'unidad_medidas';

    protected $fillable = [
        'nombre',
        'abreviatura',
        'magnitud',
    ];

      public function productos()
    {
        return $this->hasMany(Producto::class);
    }

    public function presentaciones()
    {
        return $this->hasMany(Presentacion::class);
    }
}

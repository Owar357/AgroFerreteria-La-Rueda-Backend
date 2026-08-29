<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class Producto extends Model
{
    protected $fillable = [
        'codigo',
        'nombre',
        'fabricante',
        'tipo_producto',
        'unidad_medida_id',
        'aplica_iva',
        'categoria_id',
        'registrado_por',
    ];

    protected $casts = [
        'aplica_iva' => 'boolean',
    ];

    public function categoria()
    {
        return $this->belongsTo(Categoria::class);
    }

    public function registradoPor()
    {
        return $this->belongsTo(User::class, 'registrado_por');
    }

    public function presentaciones()
    {
        return $this->hasMany(Presentacion::class);
    }

    public function unidadMedida()
    {
        return $this->belongsTo(UnidadMedida::class, 'unidad_medida_id');
    }

    public function kardex(){
        return $this->hasMany(Kardex::class);
    }
}



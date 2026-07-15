<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class AperturaCaja extends Model
{
    protected $table = 'apertura_cajas';

    protected $fillable = [
        'fecha_hora_apertura',
        'fecha_hora_cierre',
        'estado',
        'sucursal_id',
        'abierta_por',
        'cerrada_por',
    ];

    protected $casts = [
        'fecha_hora_apertura' => 'datetime',
        'fecha_hora_cierre' => 'datetime',
    ];
   
    public function aperturaVentas(){
        return  $this->hasMany(AperturaVenta::class);
    }

    public function abiertaPor(){
        return $this->belongsTo(User::class,'abierta_por');
    }

    
    public function cerradaPor(){
        return $this->belongsTo(User::class,'cerrada_por');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Kardex extends Model
{

    protected $table = "kardex";

    protected $fillable = [
        'tipo_movimiento',
        'numero_documento',
        'concepto',
        'factor_conversion',
        'cantidad_entrada',
        'cantidad_salida',
        'cantidad_saldo',
        'costo_unitario',
        'costo_promedio_ponderado',
        'monto_entrante',
        'monto_saliente',
        'monto_saldo',
        'origen_id',
        'origen_type',
        'producto_id',
        'presentacion_id',
        'lote_id',
        'usuario_id',
    ];


    protected $casts = [
        'factor_conversion' => 'decimal:4',
        'cantidad_entrada' => 'decimal:4',
        'cantidad_salida' => 'decimal:4',
        'cantidad_saldo' => 'decimal:4',
        'costo_unitario' => 'decimal:4',
        'costo_promedio_ponderado' => 'decimal:4',
        'monto_entrante' => 'decimal:2',
        'monto_saliente' => 'decimal:2',
        'monto_saldo' => 'decimal:2'
    ];



   public function origen():MorphTo{
     return $this->morphTo();
   }

    public function producto(){
        return $this->belongsTo(Producto::class);
    }

    public function presentacion(){
        return $this->belongsTo(Presentacion::class);
    }

    public function lote(){
        return $this->belongsTo(Lote::class);
    }

    public function usuario(){
        return $this->belongsTo(User::class);
    }
    

}

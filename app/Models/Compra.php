<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Compra extends Model
{
    protected $fillable = [
        'tipo_documento',
        'numero_documento',
        'fecha_emision',
        'descuentos_global',
        'iva_total',
        'monto_total',
        'estado_pago',
        'fecha_vencimiento_pago',
        'proveedor_id',
        'usuario_id',
    ];

    protected $casts = [
        'fecha_emision' => 'date',
        'fecha_vencimiento_pago' => 'date',
        'descuentos_global' => 'decimal:2',
        'iva_total' => 'decimal:2',
        'monto_total' => 'decimal:2',
    ];

    public function proveedor()
    {
        return $this->belongsTo(Proveedor::class);
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    public function detallesCompra()
    {
        return $this->hasMany(DetalleCompra::class);
    }
}

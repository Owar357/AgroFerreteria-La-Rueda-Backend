<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('compras', function (Blueprint $table) {
            $table->id();
            $table->enum('tipo_documento',(['CREDITO FISCAL','CONSUMIDOR FINAL','ORDEN DE COMPRA']));
            $table->string('numero_documento',150)->nullable()->index()->index(); 
            $table->date('fecha_emision')->nullable()->index()->index();
            $table->decimal('descuentos_global',15,2)->nullable();
            $table->decimal('iva_total',15,2)->nullable();
            $table->decimal('monto_total',15,2)->nullable();
            $table->enum('estado_pago',['PAGADO','PENDIENTE','ABONADO','VENCIDO'])->index()->index();
            $table->date('fecha_vencimiento_pago')->nullable();
            $table->foreignId('proveedor_id')->constrained('proveedores');
            $table->foreignId('usuario_id')->constrained('users');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('compras');
    }
};

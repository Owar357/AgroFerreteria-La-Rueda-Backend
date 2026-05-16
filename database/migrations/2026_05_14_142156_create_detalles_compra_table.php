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
        Schema::create('detalles_compra', function (Blueprint $table) {
            $table->id();
            $table->decimal('cantidad_facturada',15,4)->nullable();
            $table->decimal('cantidad_bonificada',15,4)->default(00.0000);
            $table->decimal('precio_unitario_factura',15,2)->nullable();
            $table->decimal('iva_linea',15,2)->nullable();
            $table->decimal('descuento_linea',15,2)->nullable();
            $table->decimal('sub_total', 15,2)->nullable();
            $table->foreignId('compra_id')->constrained('compras');
            $table->foreignId('lote_id')->constrained('lotes');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('detalles_compra');
    }
};

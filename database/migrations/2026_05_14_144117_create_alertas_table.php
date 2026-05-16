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
        Schema::create('alertas', function (Blueprint $table) {
            $table->id();
            $table->enum('tipo',['STOCK MINIMO','STOCK AGOTADO','LOTE POR VENCER','FACTURA POR VENCER'])->index();
            $table->enum('prioridad',['ALTA','MEDIA','BAJA'])->index();
            $table->boolean('leida')->default('false')->index();
            $table->foreignId('leida_por')->nullable()->constrained('users');
            $table->foreignId('lote_id')->nullable()->constrained('lotes');
            $table->foreignId('presentacion_id')->nullable()->constrained('presentaciones');
            $table->foreignId('compra_id')->nullable()->constrained('compras');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations. 
     */
    public function down(): void
    {
        Schema::dropIfExists('alertas');
    }
};

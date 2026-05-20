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
        Schema::create('lote_detalles_venta', function (Blueprint $table) {
            $table->id();
            $table->foreignId('detalle_venta_id')->constrained('detalles_venta');
            $table->foreignId('lote_id')->constrained('lotes');
            $table->integer('cantidad_tomada');
            $table->string('numero_lote');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lote_detalle_venta');
    }
};

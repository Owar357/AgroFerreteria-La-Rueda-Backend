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
        Schema::create('detalles_ajuste_inventario', function (Blueprint $table) {
            $table->id();
            $table->decimal('cantidad_sistema', 12, 4);
            $table->decimal('cantidad_fisica', 12, 4);
            $table->decimal('diferencia', 12 , 4);
            $table->decimal('costo_anterior', 12, 4);
            $table->decimal('costo_nuevo', 12,4);
            $table->decimal('monto_impacto', 12,2);
            $table->foreignId('ajuste_inventario_id')->constrained('ajustes_inventario')->onDelete('cascade');
            $table->foreignId('lote_id')->constrained('lotes');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('detalles_ajuste_inventario');
    }
};

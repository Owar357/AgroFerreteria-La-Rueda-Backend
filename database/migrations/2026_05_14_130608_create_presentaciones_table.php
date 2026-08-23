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
                Schema::create('presentaciones', function (Blueprint $table) {
                    $table->id();
                    $table->string('nombre', 150)->index();
                    $table->decimal('factor_conversion', 15, 3);
                    $table->decimal('precio_venta', 15, 4);
                    $table->decimal('stock_minimo', 15, 4)->default(0.0000);
                    $table->boolean('es_base')->default(false);
                    $table->boolean('activo')->default(true);
                    $table->timestamps();
                    $table->foreignId('producto_id')->constrained('productos')->restrictOnDelete();
                    $table->foreignId('unidad_medida_id')->constrained('unidad_medidas')->restrictOnDelete();
                });
            }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('presentaciones');
    }
};

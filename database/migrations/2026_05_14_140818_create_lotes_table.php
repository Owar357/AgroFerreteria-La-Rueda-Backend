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
        Schema::create('lotes', function (Blueprint $table) {
            $table->id(); 
            $table->string('lote_interno',13)->unique();
            $table->string('lote_fabricante',50)->nullable();
            $table->date('fecha_vencimiento')->nullable()->index();
            $table->decimal('cantidad_inicial',15,3);
            $table->decimal('cantidad_actual',15,3);
            $table->decimal('costo_unitario_compra',15,4);
            $table->decimal('porcentaje_descuento', 15,2)->nullable();
            $table->enum('estado', ['ACTIVO','DAÑADO','AGOTADO','VENCIDO'])->default('ACTIVO')->index();
            $table->foreignId('presentacion_id')->constrained('presentaciones');         
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lotes');
    }
};

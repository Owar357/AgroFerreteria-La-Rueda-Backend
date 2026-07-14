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
        Schema::create('apertura_ventas', function (Blueprint $table) {
            $table->id();
            $table->dateTime('fecha_hora_apertura')->index();
            $table->dateTime('fecha_hora_cierre')->index()->nullable();
            $table->decimal('monto_inicial',15,2);
            $table->decimal('monto_esperado',15,2)->nullable();
            $table->decimal('monto_contado',15,2)->nullable();
            $table->decimal('diferencia',15,2)->nullable();
            $table->enum('estado_arqueo',['SOBRANTE','FALTANTE','CUADRADO'])->nullable();
            $table->enum('estado',['ABIERTA','CERRADA'])->index();
            $table->foreignId('apertura_cajas_id')->constrained('apertura_cajas');
            $table->foreignId('cajero_id')->constrained('users');
            $table->foreignId('cerrada_por')->nullable()->constrained('users');  
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('apertura_ventas');
    }
};

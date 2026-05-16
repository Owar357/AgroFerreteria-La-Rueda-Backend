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
        Schema::create('turnos_caja', function (Blueprint $table) {
            $table->id();
            $table->timestamp('fecha_hora_apertura')->index();
            $table->timestamp('fecha_hora_cierre')->nullable();
            $table->decimal('monto_inicial',15,2);
            $table->decimal('monto_esperado',15,2)->nullable();
            $table->decimal('monto_real_caja',15,2)->nullable();
            $table->decimal('diferencia',15,2)->nullable();
            $table->enum('estado',['ABIERTO','CERRADO'])->index();
            $table->foreignId('abierta_por')->constrained('users');
            $table->foreignId('cerrada_por')->nullable()->constrained('users');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('turnos_caja');
    }
};

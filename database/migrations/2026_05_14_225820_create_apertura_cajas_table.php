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
        Schema::create('apertura_cajas', function (Blueprint $table) {
            $table->id();
            $table->timestamp('fecha_hora_apertura')->index();
            $table->timestamp('fecha_hora_cierre')->nullable();
            $table->enum('estado',['ABIERTO','CERRADO'])->index();
            $table->unsignedBigInteger('sucursal_id')->nullable();
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

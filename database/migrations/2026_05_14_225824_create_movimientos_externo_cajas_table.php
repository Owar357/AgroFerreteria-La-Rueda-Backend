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
        Schema::create('movimientos_externo_cajas', function (Blueprint $table) {
            $table->id();
            $table->enum('tipo_movimiento',['ENTRADA','SALIDA'])->index();
            $table->decimal('monto',15,2);
            $table->string('motivo',255);
            $table->foreignId('apertura_ventas_id')->constrained('apertura_ventas');
            $table->foreignId('usuario_id')->constrained('users');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('movimientos_caja');
    }
};

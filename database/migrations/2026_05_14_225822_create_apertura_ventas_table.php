<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

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
            $table->string('justificacion')->nullable();
            $table->enum('estado_arqueo',['SOBRANTE','FALTANTE','CUADRADO'])->nullable();
            $table->enum('estado',['ABIERTA','CERRADA'])->index();


            $table->decimal('fondo_fijo_referencia', 15, 2)->nullable(); 
            $table->string('justificacion_apertura', 500)->nullable(); 
            $table->decimal('retiro_efectivo', 15, 2)->nullable();
            $table->decimal('fondo_siguiente_turno', 15, 2)->nullable();
            $table->jsonb('denominaciones_apertura')->nullable(); 
            $table->jsonb('denominaciones_cierre')->nullable(); 


            $table->foreignId('apertura_caja_id')->constrained('apertura_cajas');
            $table->foreignId('cajero_id')->constrained('users');
            $table->foreignId('cerrada_por')->nullable()->constrained('users');  
            $table->timestamps();
        });
        // Candado de seguridad unificados (Solo permite un turno y una caja abiertos a la vez):
        DB::statement(
            "CREATE UNIQUE INDEX apertura_ventas_una_abierta ON apertura_ventas ((1)) WHERE estado = 'ABIERTA'"
        );
 
        DB::statement(
            "CREATE UNIQUE INDEX apertura_cajas_una_abierta ON apertura_cajas ((1)) WHERE estado = 'ABIERTO'"
        );
       
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
         DB::statement('DROP INDEX IF EXISTS apertura_cajas_una_abierta');
        DB::statement('DROP INDEX IF EXISTS apertura_ventas_una_abierta');
        
        Schema::dropIfExists('apertura_ventas');
    }
};

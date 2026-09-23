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
        Schema::table('apertura_ventas', function (Blueprint $table) {
            // Fondo fijo vigente cuando se abrió el turno (se reutiliza al cerrar el mismo turno)
            $table->decimal('fondo_fijo_referencia', 15, 2)->nullable();
 
            // Obligatoria cuando el monto contado al abrir es distinto del fondo fijo
            $table->string('justificacion_apertura', 500)->nullable();
 
            // Destino del dinero al cierre
            $table->decimal('retiro_efectivo', 15, 2)->nullable();
            $table->decimal('fondo_siguiente_turno', 15, 2)->nullable();
 
            // Desglose de monedas y billetes contado por el cajero / admin
            $table->jsonb('denominaciones_apertura')->nullable();
            $table->jsonb('denominaciones_cierre')->nullable();
        });
 
        Schema::table('ventas', function (Blueprint $table) {
            $table->string('motivo_anulacion', 255)->nullable();
        });
 
        // Solo puede existir UNA apertura de venta ABIERTA en todo el sistema (gaveta única)
        DB::statement(
            "CREATE UNIQUE INDEX apertura_ventas_una_abierta ON apertura_ventas ((1)) WHERE estado = 'ABIERTA'"
        );
 
        // Solo puede existir UNA apertura de caja ABIERTA
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
 
        Schema::table('ventas', function (Blueprint $table) {
            $table->dropColumn('motivo_anulacion');
        });
 
        Schema::table('apertura_ventas', function (Blueprint $table) {
            $table->dropColumn([
                'fondo_fijo_referencia',
                'justificacion_apertura',
                'retiro_efectivo',
                'fondo_siguiente_turno',
                'denominaciones_apertura',
                'denominaciones_cierre',
            ]);
        });
    }
    
};

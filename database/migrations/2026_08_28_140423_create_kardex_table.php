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
        Schema::create('kardex', function (Blueprint $table) {
            $table->id();
            $table->enum('tipo_movimiento', [
                'ENTRADA_COMPRA',
                'SALIDA_VENTA',
                'ANULACION_COMPRA',
                'ANULACION_VENTA',
                'AJUSTE_POSITIVO',  
                'AJUSTE_NEGATIVO',   
                'REEVALUACION_COSTO', 
                'CAMBIO_PRESENTACION', 
            ])->index();
            $table->string('numero_documento', 50)->nullable()->index();
            $table->string('concepto', 255)->nullable();

            $table->decimal('factor_conversion', 15, 4)->default(1.0000);
            $table->decimal('cantidad_entrada', 15, 4)->default(0.0000);
            $table->decimal('cantidad_salida', 15, 4)->default(0.0000);
            $table->decimal('cantidad_saldo', 15, 4);

            $table->decimal('costo_unitario', 15, 4);
            $table->decimal('costo_promedio_ponderado', 15, 4)->nullable();
            $table->decimal('monto_entrante', 15, 2)->default(0.00);
            $table->decimal('monto_saliente', 15, 2)->default(0.00);
            $table->decimal('monto_saldo', 15, 2);

            $table->nullableMorphs('origen');
            $table->foreignId('producto_id')->constrained('productos')->restrictOnDelete();
            $table->foreignId('presentacion_id')->nullable()->constrained('presentaciones')->restrictOnDelete();
            $table->foreignId('lote_id')->nullable()->constrained('lotes')->nullOnDelete();
            $table->foreignId('usuario_id')->constrained('users')->restrictOnDelete();

            $table->timestamps();

            $table->index(['producto_id', 'created_at']);
            $table->index(['presentacion_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kardex');
    }
};

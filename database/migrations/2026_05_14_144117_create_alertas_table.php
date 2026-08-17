<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alertas', function (Blueprint $table) {
            $table->id();
            $table->string('mensaje');
            $table->enum('estado', ['ACTIVA', 'RESUELTA'])->default('ACTIVA')->index();
            $table->timestamp('ultima_notificacion_at')->nullable();
            $table->enum('tipo', ['STOCK MINIMO', 'STOCK AGOTADO', 'LOTE POR VENCER', 'LOTE VENCIDO', 'COMPRA PENDIENTE DE PAGO', 'COMPRA VENCIDA'])->index();
            $table->enum('prioridad', ['ALTA', 'MEDIA', 'BAJA'])->index();
            $table->boolean('leida')->default(false)->index();
            $table->foreignId('leida_por')->nullable()->constrained('users');
            $table->foreignId('lote_id')->nullable()->constrained('lotes');
            $table->foreignId('producto_id')->nullable()->constrained('productos');
            $table->foreignId('compra_id')->nullable()->constrained('compras');
            $table->timestamps();
        });

        DB::statement("
            CREATE UNIQUE INDEX alertas_activa_lote_unique
            ON alertas (tipo, lote_id)
            WHERE estado = 'ACTIVA' AND lote_id IS NOT NULL
        ");

        DB::statement("
            CREATE UNIQUE INDEX alertas_activa_producto_unique
            ON alertas (tipo, producto_id)
            WHERE estado = 'ACTIVA' AND producto_id IS NOT NULL
        ");

        DB::statement("
            CREATE UNIQUE INDEX alertas_activa_compra_unique
            ON alertas (tipo, compra_id)
            WHERE estado = 'ACTIVA' AND compra_id IS NOT NULL
        ");
    }

    public function down(): void
    {
        Schema::dropIfExists('alertas');
    }
};
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

use function Laravel\Prompts\table;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('ajustes_inventario', function (Blueprint $table) {
            $table->id();
            $table->string('numero_ajuste',20)->unique();
            $table->enum('tipo_ajuste',['INCREMENTO','DISMINUCION','REEVALUACION']);
            $table->string('motivo',100);
            $table->string('observaciones',500)->nullable();
            $table->foreignId('usuario_id')->constrained('users'); 
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ajustes_inventario');
    }
};

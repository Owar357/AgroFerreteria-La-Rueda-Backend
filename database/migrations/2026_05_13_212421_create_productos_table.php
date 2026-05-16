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
    { Schema::create('productos', function (Blueprint $table) {
            $table->id();
            $table->string('codigo',14)->unique();
            $table->string('nombre',100)->index();
            $table->string('fabricante',100)->nullable();
            $table->enum('tipo_producto',['UNIDAD FIJA','GRANEL'])->index();
            $table->string('unidad_base',20);
            $table->boolean('aplica_iva')->default(false);
            $table->foreignId('categoria_id')->constrained('categorias')->restrictOnDelete();
            $table->foreignId('registrado_por')->constrained('users')->restrictOnDelete();
            $table->timestamps();
        });
    }
       

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('productos');
    }
};

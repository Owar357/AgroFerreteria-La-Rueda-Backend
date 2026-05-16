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
            Schema::create('codigos_barras', function (Blueprint $table) {
                $table->id();
                $table->string('codigo',50)->unique();
                $table->boolean('activo')->default(true);
                $table->foreignId('presentacion_id')->nullable()->constrained('presentaciones');
                $table->timestamps();

            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('codigos_barras');
    }
};

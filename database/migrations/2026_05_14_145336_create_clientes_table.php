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
        Schema::create('clientes', function (Blueprint $table) {
            $table->id();
            $table->enum('tipo_persona', ['JURIDICA', 'NATURAL']);
            $table->string('nombre',250)->nullable()->index();
            $table->string('razon_social',250)->nullable()->index();
            $table->char('tipo_documento_receptor',2)->nullable()
        ->comment('13=DUI,36=NIT,02=Pasaporte,03=Carnet Residente');
            $table->string('numero_documento', 20)->nullable();
            $table->string('nrc', 15)->nullable();
            $table->string('cod_actividad',10)->nullable();
            $table->string('giro_actividad',250)->nullable();
            
            $table->char('cod_departamento', 2)->nullable();
            $table->char('cod_municipio', 4)->nullable();
            $table->string('complemento', 250)->nullable();
      
            $table->string('correo', 150)->nullable();
            $table->boolean('activo')->default(true)->index();
            $table->foreignId('registrado_por')->nullable()->constrained('users');
            $table->timestamps();

            $table->unique([
                'tipo_documento_receptor',
                'numero_documento',
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clientes');
    }
};

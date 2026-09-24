<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ventas', function (Blueprint $table) {
            // Códigos DTE: 01 = Consumidor Final, 03 = Comprobante de Crédito Fiscal.
            // Las ventas anteriores quedan como Consumidor Final.
            $table->string('tipo_factura', 2)->default('01');
        });
    }

    public function down(): void
    {
        Schema::table('ventas', function (Blueprint $table) {
            $table->dropColumn('tipo_factura');
        });
    }
};   
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
        Schema::create('detalles_venta', function (Blueprint $table) {
            $table->id();
            $table->string('nombre_producto',150);
            $table->string('presentacion',100);
            $table->decimal('cantidad',15,4);
            $table->decimal('precio_unitario',15,4);
            $table->decimal('subtotal',15,2);
            $table->decimal('iva_aplicado',15,2)->default(0.00);
            $table->decimal('descuento_aplicado',15,2);
            $table->foreignId('venta_id')->constrained('ventas');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('detalles_venta');
    }
};

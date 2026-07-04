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
            Schema::create('ventas', function (Blueprint $table) {
                $table->id();
                $table->string('numero_factura',20)->unique();
                $table->enum('tipo_pago',['EFECTIVO', 'TARJETA', 'TRANSFERENCIA']);
                $table->enum('estado',['PROCESADA','ANULADA'])->default('PROCESADA')->index();
                $table->decimal('gravado',15,2);
                $table->decimal('exento',15,2);
                $table->decimal('iva',15,2)->default(00.00);
                $table->decimal('total',15,2);
                $table->decimal('efectivo_recibido',15,2)->nullable();
                $table->decimal('cambio',15,2)->nullable();
                $table->timestamp('fecha_hora_anulacion')->nullable();
                $table->foreignId('cliente_id')->nullable()->constrained('clientes');
                $table->foreignId('vendido_por')->constrained('users');
                $table->foreignId('anulado_por')->nullable()->constrained('users');
                $table->foreignId('apertura_caja_id')->constrained('turnos_caja');
                $table->timestamps();
                $table->index('created_at');
            });
        }

        /**
         * Reverse the migrations.
         */
        public function down(): void
        {
            Schema::dropIfExists('ventas');
        }
    };

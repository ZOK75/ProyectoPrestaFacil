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
        Schema::create('prestamos', function (Blueprint $table) {
            $table->id();

            // Referencia única de la cuenta
            $table->string('referencia', 50)->unique();

            // Relaciones
            $table->foreignId('cliente_id')->constrained('clientes')->cascadeOnDelete();
            $table->foreignId('producto_vale_id')->constrained('producto_vales')->cascadeOnDelete();

            // Clasificación (prevale vs vale)
            $table->enum('tipo', ['prevale', 'vale'])->default('prevale');

            // Montos y plazos
            $table->decimal('monto_prestamo', 10, 2);
            $table->decimal('cuota_quincenal', 10, 2);
            $table->integer('pagos_totales'); // Plazo en quincenas
            $table->integer('pagos_realizados')->default(0);
            $table->decimal('monto_total_pagar', 10, 2);
            $table->decimal('adeudo_pendiente', 10, 2);
            $table->decimal('pagos_recibidos', 10, 2)->default(0);
            $table->decimal('multas', 10, 2)->default(0);

            // Estado de la cuenta
            $table->enum('estado', ['activo', 'finalizado', 'desactivado'])->default('activo');
            $table->boolean('activo')->default(true);

            // Auditoría y Desactivación
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('desactivado_at')->nullable();
            $table->foreignId('desactivado_by_user_id')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('prestamos');
    }
};

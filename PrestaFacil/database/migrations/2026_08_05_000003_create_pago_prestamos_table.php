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
        Schema::create('pago_prestamos', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('prestamo_id')->constrained('prestamos')->cascadeOnDelete();
            $table->string('folio_pago', 50)->unique();
            $table->integer('numero_quincena');
            $table->decimal('monto_abonado', 10, 2);
            $table->decimal('monto_multa', 10, 2)->default(0);
            $table->string('metodo_pago')->default('Efectivo');
            $table->text('observaciones')->nullable();
            $table->foreignUuid('registrado_por_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pago_prestamos');
    }
};

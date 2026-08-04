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
        Schema::create('configuracion_generales', function (Blueprint $table) {
            $table->id();
 
            // Parámetros de negocio (afectan a todas las sucursales)
            $table->dateTime('fecha_corte');
            $table->dateTime('fecha_limite_pago');
            $table->decimal('multa_adeudo', 10, 2)->default(0);
 
            // Auditoría de quién crea / modifica la configuración
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by_user_id')->nullable()->constrained('users')->nullOnDelete();
 
            $table->timestamps();
        });

        // Historial de cambios de la configuración general
        Schema::create('configuracion_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('configuracion_id')->constrained('configuracion_generales')->cascadeOnDelete();

            // Snapshot de los valores al momento del cambio
            $table->dateTime('fecha_corte');
            $table->dateTime('fecha_limite_pago');
            $table->decimal('multa_adeudo', 10, 2)->default(0);

            // Quién hizo el cambio
            $table->foreignId('changed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('motivo')->nullable();

            $table->timestamp('changed_at');
        });
    }
 
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('configuracion_logs');
        Schema::dropIfExists('configuracion_generales');
    }
};
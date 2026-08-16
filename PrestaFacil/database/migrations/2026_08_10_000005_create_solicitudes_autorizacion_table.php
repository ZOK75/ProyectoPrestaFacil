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
        Schema::create('solicitudes_autorizacion', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->enum('tipo', ['modificacion_datos', 'conciliacion_manual']);
            $table->enum('estado', ['pendiente', 'aprobada', 'rechazada'])->default('pendiente');
            
            $table->foreignUuid('solicitante_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUuid('sucursal_id')->constrained('sucursales')->cascadeOnDelete();
            
            // Polimorfismo manual para ligar al cliente o al préstamo/pago
            $table->string('entidad_tipo');
            $table->uuid('entidad_id');

            $table->json('datos_originales')->nullable();
            $table->json('datos_propuestos')->nullable();
            $table->text('motivo');
            $table->string('evidencia_path')->nullable();

            $table->foreignUuid('autorizador_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('autorizador_rol')->nullable();
            
            $table->text('observaciones_resolucion')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('solicitudes_autorizacion');
    }
};

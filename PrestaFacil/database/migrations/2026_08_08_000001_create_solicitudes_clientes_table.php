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
        Schema::create('solicitudes_clientes', function (Blueprint $table) {
            $table->id();

            // Tipo de solicitud: 'actualizacion' o 'desactivacion'
            $table->enum('tipo', ['actualizacion', 'desactivacion']);

            // Estado de la solicitud: 'pendiente', 'aprobada', 'rechazada'
            $table->enum('estado', ['pendiente', 'aprobada', 'rechazada'])->default('pendiente');

            // Cliente sobre el que se hace la solicitud
            $table->foreignId('cliente_id')
                ->constrained('clientes')
                ->cascadeOnDelete();

            // Distribuidor que origina la solicitud
            $table->foreignId('distribuidor_id')
                ->constrained('users')
                ->cascadeOnDelete();

            // Sucursal a la que pertenece el cliente/distribuidor
            $table->foreignId('sucursal_id')
                ->nullable()
                ->constrained('sucursales')
                ->nullOnDelete();

            // Datos actuales y propuestos en formato JSON
            $table->json('datos_originales')->nullable();
            $table->json('datos_solicitados')->nullable();

            // Motivo o justificación de la solicitud
            $table->text('motivo')->nullable();

            // Rutas a nuevos archivos PDF temporales si aplica
            $table->string('pdf_ine_nuevo')->nullable();
            $table->string('pdf_comprobante_nuevo')->nullable();

            // Resolución por parte de Gerencia (General o Sucursal)
            $table->foreignId('aprobado_por_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->foreignId('rechazado_por_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

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
        Schema::dropIfExists('solicitudes_clientes');
    }
};

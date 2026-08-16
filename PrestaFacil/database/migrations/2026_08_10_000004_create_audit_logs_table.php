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
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('tipo_operacion');
            $table->text('descripcion');
            $table->json('datos_antes')->nullable();
            $table->json('datos_despues')->nullable();
            
            // Usuario que realizó la acción
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('user_rol');

            // Autorizador si aplica
            $table->foreignUuid('autorizador_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('autorizador_rol')->nullable();

            // Sucursal donde se ejecutó
            $table->foreignUuid('sucursal_id')->nullable()->constrained('sucursales')->nullOnDelete();

            // Entidad afectada (Polimorfismo manual)
            $table->string('entidad_tipo')->nullable();
            $table->uuid('entidad_id')->nullable();

            $table->string('evidencia_path')->nullable();
            $table->string('ip_address')->nullable();
            
            $table->timestamp('created_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};

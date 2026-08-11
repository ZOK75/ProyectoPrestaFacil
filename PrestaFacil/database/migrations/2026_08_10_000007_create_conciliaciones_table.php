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
        Schema::create('conciliaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('prestamo_id')->constrained('prestamos')->cascadeOnDelete();
            $table->foreignId('pago_prestamo_id')->nullable()->constrained('pago_prestamos')->nullOnDelete();
            
            $table->decimal('monto_original', 10, 2);
            $table->decimal('monto_corregido', 10, 2);
            $table->text('motivo');
            $table->string('evidencia_path')->nullable();
            
            $table->foreignId('solicitante_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('autorizador_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('autorizador_rol')->nullable();
            
            $table->enum('estado', ['pendiente', 'aprobada', 'rechazada'])->default('pendiente');
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
        Schema::dropIfExists('conciliaciones');
    }
};

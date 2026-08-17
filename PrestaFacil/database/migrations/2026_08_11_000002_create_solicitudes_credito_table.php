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
        Schema::create('solicitudes_credito', function (Blueprint $table) {
            $table->uuid('id')->primary();
            
            $table->foreignUuid('distribuidor_id')->constrained('users')->onDelete('cascade');
            $table->foreignUuid('coordinador_id')->constrained('users')->onDelete('cascade');
            $table->foreignUuid('gerente_id')->nullable()->constrained('users')->nullOnDelete();
            
            $table->decimal('limite_actual', 10, 2);
            $table->decimal('limite_nuevo', 10, 2);
            $table->text('motivo');
            $table->enum('estado', ['pendiente', 'aprobado', 'rechazado'])->default('pendiente');
            $table->text('observaciones')->nullable();
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('solicitudes_credito');
    }
};

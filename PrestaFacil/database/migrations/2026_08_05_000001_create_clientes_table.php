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
        Schema::create('clientes', function (Blueprint $table) {
            $table->id();

            // Datos Personales
            $table->string('nombre');
            $table->string('curp', 18)->unique();
            $table->string('rfc', 13)->nullable();
            $table->date('fecha_nacimiento');
            $table->string('lugar_nacimiento');

            // Dirección Completa
            $table->string('calle');
            $table->string('colonia');
            $table->string('codigo_postal', 5);
            $table->string('ciudad');
            $table->string('estado');

            // Expedientes en PDF
            $table->string('path_ine_pdf')->nullable();
            $table->string('path_comprobante_pdf')->nullable();

            // Auditoría y Desactivación
            $table->boolean('activo')->default(true);
            $table->timestamp('desactivado_at')->nullable();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('desactivado_by_user_id')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clientes');
    }
};

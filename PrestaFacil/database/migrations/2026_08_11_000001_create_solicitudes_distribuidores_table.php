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
        Schema::create('solicitudes_distribuidores', function (Blueprint $table) {
            $table->id();
            
            // Datos Personales
            $table->string('nombres');
            $table->string('apellidos');
            $table->string('acta_nacimiento')->nullable();
            $table->string('curp');
            $table->string('rfc');
            $table->string('lugar_nacimiento')->nullable();
            
            // Dirección
            $table->string('calle');
            $table->string('colonia');
            $table->string('codigo_postal');
            $table->string('estado_republica');
            $table->string('ciudad');
            
            // Datos Extra
            $table->json('datos_familiares')->nullable();
            $table->text('datos_vehiculos')->nullable();
            $table->text('datos_casa')->nullable();
            $table->text('referencias_laborales')->nullable();
            
            // Relaciones de Usuarios (Sistema)
            $table->foreignId('coordinador_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('sucursal_id')->constrained('sucursals')->onDelete('cascade');
            $table->foreignId('verificador_id')->nullable()->constrained('users')->nullOnDelete();
            
            // Estado y Control
            $table->enum('estado', ['en espera', 'en espera de verificacion', 'aprobado', 'rechazado'])->default('en espera');
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
        Schema::dropIfExists('solicitudes_distribuidores');
    }
};

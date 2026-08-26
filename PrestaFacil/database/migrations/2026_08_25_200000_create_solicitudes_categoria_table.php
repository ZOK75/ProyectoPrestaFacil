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
        Schema::create('solicitudes_categoria', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('distribuidor_id')->constrained('users')->onDelete('cascade');
            $table->foreignUuid('coordinador_id')->constrained('users')->onDelete('cascade');
            $table->foreignUuid('gerente_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('categoria_actual')->default('cobre');
            $table->string('categoria_nueva');
            $table->text('motivo');
            $table->string('estado')->default('pendiente'); // pendiente, aprobado, rechazado
            $table->text('observaciones')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('solicitudes_categoria');
    }
};

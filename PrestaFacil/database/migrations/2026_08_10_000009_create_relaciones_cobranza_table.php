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
        Schema::create('relaciones_cobranza', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('distribuidora_id')->constrained('users')->cascadeOnDelete();
            $table->dateTime('fecha_corte');
            $table->dateTime('fecha_limite_pago');
            $table->decimal('monto_total_periodo', 10, 2)->default(0);
            $table->decimal('monto_pagado', 10, 2)->default(0);
            $table->decimal('adeudo_pendiente', 10, 2)->default(0);
            $table->decimal('multa_aplicada', 10, 2)->default(0);
            $table->enum('estado_pago', ['pendiente', 'pago_anticipado', 'pago_a_tiempo', 'pago_atrasado'])->default('pendiente');
            $table->integer('puntos_ganados')->default(0);
            $table->integer('puntos_descontados')->default(0);
            $table->timestamp('corte_notificado_at')->nullable();
            $table->timestamp('multa_aplicada_at')->nullable();
            $table->timestamp('liquidado_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('relaciones_cobranza');
    }
};

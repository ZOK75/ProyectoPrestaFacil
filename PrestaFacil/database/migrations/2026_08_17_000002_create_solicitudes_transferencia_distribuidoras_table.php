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
        Schema::create('solicitudes_transferencias', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('distribuidor_id')
                ->constrained('users', 'id', 'fk_st_distribuidor')
                ->cascadeOnDelete();

            $table->foreignUuid('coordinador_emisor_id')
                ->constrained('users', 'id', 'fk_st_coordinador_emisor')
                ->cascadeOnDelete();

            $table->foreignUuid('coordinador_receptor_id')
                ->constrained('users', 'id', 'fk_st_coordinador_receptor')
                ->cascadeOnDelete();

            $table->foreignUuid('sucursal_origen_id')
                ->constrained('sucursales', 'id', 'fk_st_sucursal_origen')
                ->cascadeOnDelete();

            $table->foreignUuid('sucursal_destino_id')
                ->constrained('sucursales', 'id', 'fk_st_sucursal_destino')
                ->cascadeOnDelete();

            $table->text('motivo');
            $table->enum('estado', [
                'pendiente_coordinador',
                'pendiente_gerente',
                'aprobada',
                'rechazada_coordinador',
                'rechazada_gerente'
            ])->default('pendiente_coordinador');

            $table->text('observaciones_coordinador_receptor')->nullable();
            $table->text('observaciones_gerente')->nullable();
            $table->foreignUuid('gerente_id')
                ->nullable()
                ->constrained('users', 'id', 'fk_st_gerente')
                ->nullOnDelete();

            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('solicitudes_transferencias');
    }
};

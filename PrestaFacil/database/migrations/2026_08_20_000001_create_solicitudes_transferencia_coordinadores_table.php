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
        Schema::create('solicitudes_transferencia_coordinadores', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('coordinador_id')
                ->constrained('users', 'id', 'fk_stc_coordinador')
                ->cascadeOnDelete();

            $table->foreignUuid('gerente_emisor_id')
                ->constrained('users', 'id', 'fk_stc_gerente_emisor')
                ->cascadeOnDelete();

            $table->foreignUuid('gerente_receptor_id')
                ->constrained('users', 'id', 'fk_stc_gerente_receptor')
                ->cascadeOnDelete();

            $table->foreignUuid('sucursal_origen_id')
                ->constrained('sucursales', 'id', 'fk_stc_sucursal_origen')
                ->cascadeOnDelete();

            $table->foreignUuid('sucursal_destino_id')
                ->constrained('sucursales', 'id', 'fk_stc_sucursal_destino')
                ->cascadeOnDelete();

            $table->text('motivo');
            $table->enum('estado', [
                'pendiente_gerente_receptor',
                'pendiente_gerente_general',
                'aprobada',
                'rechazada_gerente_receptor',
                'rechazada_gerente_general'
            ])->default('pendiente_gerente_receptor');

            $table->text('observaciones_gerente_receptor')->nullable();
            $table->text('observaciones_gerente_general')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('solicitudes_transferencia_coordinadores');
    }
};

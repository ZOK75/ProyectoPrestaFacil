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
        Schema::create('solicitudes_traspaso_clientes', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('cliente_id')
                ->constrained('clientes', 'id', 'fk_stc_cliente')
                ->cascadeOnDelete();

            $table->foreignUuid('distribuidor_emisor_id')
                ->constrained('users', 'id', 'fk_stc_distribuidor_emisor')
                ->cascadeOnDelete();

            $table->foreignUuid('distribuidor_receptor_id')
                ->constrained('users', 'id', 'fk_stc_distribuidor_receptor')
                ->cascadeOnDelete();

            $table->foreignUuid('coordinador_id')
                ->nullable()
                ->constrained('users', 'id', 'fk_stc_coordinador_eval')
                ->nullOnDelete();

            $table->text('motivo');
            $table->enum('estado', [
                'pendiente_distribuidor_receptor',
                'pendiente_coordinador',
                'aprobada',
                'rechazada_distribuidor_receptor',
                'rechazada_coordinador'
            ])->default('pendiente_distribuidor_receptor');

            $table->text('observaciones_distribuidor_receptor')->nullable();
            $table->text('observaciones_coordinador')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('solicitudes_traspaso_clientes');
    }
};

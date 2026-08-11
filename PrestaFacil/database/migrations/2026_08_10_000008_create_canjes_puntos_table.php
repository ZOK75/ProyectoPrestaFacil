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
        Schema::create('canjes_puntos', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('distribuidora_id')->constrained('users')->cascadeOnDelete();
            $table->integer('puntos_canjeados');
            $table->decimal('valor_punto', 10, 2);
            $table->decimal('equivalente_dinero', 10, 2);
            $table->decimal('sobrante_devuelto', 10, 2)->default(0);
            
            $table->foreignUuid('cajera_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUuid('sucursal_id')->constrained('sucursales')->cascadeOnDelete();
            
            $table->timestamp('created_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('canjes_puntos');
    }
};

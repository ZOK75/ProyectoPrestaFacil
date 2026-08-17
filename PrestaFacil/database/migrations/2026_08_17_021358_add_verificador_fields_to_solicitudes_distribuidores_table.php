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
        Schema::table('solicitudes_distribuidores', function (Blueprint $table) {
            $table->enum('dictamen_verificador', ['pendiente', 'aceptado', 'rechazado'])->default('pendiente')->after('estado');
            $table->text('comentarios_verificador')->nullable()->after('dictamen_verificador');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('solicitudes_distribuidores', function (Blueprint $table) {
            $table->dropColumn(['dictamen_verificador', 'comentarios_verificador']);
        });
    }
};

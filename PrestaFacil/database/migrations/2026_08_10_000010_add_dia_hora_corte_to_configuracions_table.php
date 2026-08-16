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
        Schema::table('configuracion_generales', function (Blueprint $table) {
            $table->integer('dia_corte')->default(10)->after('fecha_corte');
            $table->time('hora_corte')->default('22:20:00')->after('dia_corte');
            $table->integer('dia_limite_pago')->default(15)->after('fecha_limite_pago');
            $table->time('hora_limite_pago')->default('23:59:00')->after('dia_limite_pago');
            
            // Permitir que fecha_corte y fecha_limite_pago sean nullables o computadas
            $table->dateTime('fecha_corte')->nullable()->change();
            $table->dateTime('fecha_limite_pago')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('configuracion_generales', function (Blueprint $table) {
            $table->dropColumn(['dia_corte', 'hora_corte', 'dia_limite_pago', 'hora_limite_pago']);
        });
    }
};

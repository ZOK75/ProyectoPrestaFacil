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
            if (!Schema::hasColumn('configuracion_generales', 'monto_base_puntos')) {
                $table->decimal('monto_base_puntos', 10, 2)->default(1200.00)->after('comision_oro');
            }
            if (!Schema::hasColumn('configuracion_generales', 'puntos_por_monto_base')) {
                $table->integer('puntos_por_monto_base')->default(3)->after('monto_base_puntos');
            }
            if (!Schema::hasColumn('configuracion_generales', 'valor_punto')) {
                $table->decimal('valor_punto', 10, 2)->default(2.00)->after('puntos_por_monto_base');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('configuracion_generales', function (Blueprint $table) {
            $columnsToDrop = [];
            if (Schema::hasColumn('configuracion_generales', 'monto_base_puntos')) {
                $columnsToDrop[] = 'monto_base_puntos';
            }
            if (Schema::hasColumn('configuracion_generales', 'puntos_por_monto_base')) {
                $columnsToDrop[] = 'puntos_por_monto_base';
            }
            if (count($columnsToDrop) > 0) {
                $table->dropColumn($columnsToDrop);
            }
        });
    }
};

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
            // Regla del prevale (porcentaje máximo sobre el límite de crédito)
            $table->decimal('porcentaje_regla_prevale', 5, 2)->default(50.00)->after('comision_oro');
            $table->decimal('tolerancia_regla_prevale', 10, 2)->default(500.00)->after('porcentaje_regla_prevale');

            // Sistema de puntos
            $table->decimal('valor_punto', 10, 2)->default(10.00)->after('tolerancia_regla_prevale');
            $table->integer('puntos_por_relacion')->default(5)->after('valor_punto');
            $table->decimal('penalizacion_morosidad_puntos', 5, 2)->default(20.00)->after('puntos_por_relacion');
            $table->integer('multiplo_canje_puntos')->default(20)->after('penalizacion_morosidad_puntos');

            // Productos y morosidad
            $table->integer('multiplo_producto')->default(100)->after('multiplo_canje_puntos');
            $table->integer('strikes_morosidad')->default(3)->after('multiplo_producto');

            // Segunda fecha de pago global
            $table->dateTime('fecha_pago_2')->nullable()->after('fecha_limite_pago');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('configuracion_generales', function (Blueprint $table) {
            $table->dropColumn([
                'porcentaje_regla_prevale',
                'tolerancia_regla_prevale',
                'valor_punto',
                'puntos_por_relacion',
                'penalizacion_morosidad_puntos',
                'multiplo_canje_puntos',
                'multiplo_producto',
                'strikes_morosidad',
                'fecha_pago_2',
            ]);
        });
    }
};

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
            $table->decimal('comision_cobre', 5, 2)->default(3.00)->after('multa_adeudo');
            $table->decimal('comision_plata', 5, 2)->default(6.00)->after('comision_cobre');
            $table->decimal('comision_oro', 5, 2)->default(10.00)->after('comision_plata');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('configuracion_generales', function (Blueprint $table) {
            $table->dropColumn(['comision_cobre', 'comision_plata', 'comision_oro']);
        });
    }
};

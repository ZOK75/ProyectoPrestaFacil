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
        Schema::table('conciliaciones', function (Blueprint $table) {
            if (!Schema::hasColumn('conciliaciones', 'prestamos_asignados')) {
                $table->json('prestamos_asignados')->nullable()->after('pago_prestamo_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('conciliaciones', function (Blueprint $table) {
            if (Schema::hasColumn('conciliaciones', 'prestamos_asignados')) {
                $table->dropColumn('prestamos_asignados');
            }
        });
    }
};

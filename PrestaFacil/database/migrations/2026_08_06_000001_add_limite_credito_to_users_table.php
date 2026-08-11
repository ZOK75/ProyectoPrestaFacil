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
        Schema::table('users', function (Blueprint $table) {
            $table->decimal('limite_credito', 10, 2)->default(20000.00)->after('categoria_distribuidor');
            $table->string('referencia_pago_distribuidor', 50)->nullable()->after('limite_credito');
            $table->integer('puntos')->default(0)->after('referencia_pago_distribuidor');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['limite_credito', 'referencia_pago_distribuidor', 'puntos']);
        });
    }
};

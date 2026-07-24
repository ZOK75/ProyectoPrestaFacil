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

            $table->foreignId('rol_id')
                ->nullable()
                ->after('password')
                ->constrained('roles')
                ->nullOnDelete();

            $table->foreignId('sucursal_id')
                ->nullable()
                ->after('rol_id')
                ->constrained('sucursales')
                ->nullOnDelete();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {

            $table->dropForeign(['rol_id']);
            $table->dropForeign(['sucursal_id']);

            $table->dropColumn('rol_id');
            $table->dropColumn('sucursal_id');

        });
    }
};

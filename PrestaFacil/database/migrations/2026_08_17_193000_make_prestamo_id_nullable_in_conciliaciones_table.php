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
            $table->uuid('prestamo_id')->nullable()->change();
            $table->string('estado', 50)->default('pendiente')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('conciliaciones', function (Blueprint $table) {
            $table->uuid('prestamo_id')->nullable(false)->change();
        });
    }
};

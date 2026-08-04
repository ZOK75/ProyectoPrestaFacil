<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('activo')->default(true)->after('sucursal_id');
            $table->timestamp('desactivado_at')->nullable()->after('activo');
            $table->foreignId('desactivado_by_user_id')->nullable()->after('desactivado_at')
                ->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['desactivado_by_user_id']);
            $table->dropColumn(['activo', 'desactivado_at', 'desactivado_by_user_id']);
        });
    }
};

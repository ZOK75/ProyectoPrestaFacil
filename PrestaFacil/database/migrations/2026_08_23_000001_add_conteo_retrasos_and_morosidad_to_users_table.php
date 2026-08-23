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
            $table->integer('conteo_retrasos')->default(0)->after('multas');
            $table->boolean('es_morosa')->default(false)->after('conteo_retrasos');
            $table->timestamp('morosa_at')->nullable()->after('es_morosa');
            $table->foreignUuid('morosa_by_user_id')->nullable()->after('morosa_at')->constrained('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['morosa_by_user_id']);
            $table->dropColumn([
                'conteo_retrasos',
                'es_morosa',
                'morosa_at',
                'morosa_by_user_id',
            ]);
        });
    }
};

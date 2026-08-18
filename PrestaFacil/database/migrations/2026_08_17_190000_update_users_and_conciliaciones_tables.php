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
            if (!Schema::hasColumn('users', 'multas')) {
                $table->decimal('multas', 10, 2)->default(0.00)->after('limite_credito');
            }
        });

        Schema::table('conciliaciones', function (Blueprint $table) {
            if (!Schema::hasColumn('conciliaciones', 'distribuidora_id')) {
                $table->foreignUuid('distribuidora_id')->nullable()->after('pago_prestamo_id')->constrained('users')->nullOnDelete();
            }
            if (!Schema::hasColumn('conciliaciones', 'referencia_original')) {
                $table->string('referencia_original')->nullable()->after('distribuidora_id');
            }
            if (!Schema::hasColumn('conciliaciones', 'referencia_conciliacion')) {
                $table->string('referencia_conciliacion')->nullable()->after('referencia_original');
            }
            if (!Schema::hasColumn('conciliaciones', 'fecha_pago')) {
                $table->date('fecha_pago')->nullable()->after('referencia_conciliacion');
            }
            if (!Schema::hasColumn('conciliaciones', 'metodo_pago')) {
                $table->string('metodo_pago')->nullable()->after('fecha_pago');
            }
            if (!Schema::hasColumn('conciliaciones', 'conciliado_por_user_id')) {
                $table->foreignUuid('conciliado_por_user_id')->nullable()->after('autorizador_rol')->constrained('users')->nullOnDelete();
            }
            if (!Schema::hasColumn('conciliaciones', 'conciliado_at')) {
                $table->timestamp('conciliado_at')->nullable()->after('resolved_at');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'multas')) {
                $table->dropColumn('multas');
            }
        });

        Schema::table('conciliaciones', function (Blueprint $table) {
            $table->dropForeign(['distribuidora_id']);
            $table->dropForeign(['conciliado_por_user_id']);
            $table->dropColumn([
                'distribuidora_id',
                'referencia_original',
                'referencia_conciliacion',
                'fecha_pago',
                'metodo_pago',
                'conciliado_por_user_id',
                'conciliado_at'
            ]);
        });
    }
};

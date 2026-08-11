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
        Schema::table('prestamos', function (Blueprint $table) {
            $table->enum('estado_entrega', ['pendiente', 'entregado', 'cancelado'])->default('pendiente')->after('estado');
            $table->foreignId('entregado_por_user_id')->nullable()->constrained('users')->nullOnDelete()->after('created_by_user_id');
            $table->timestamp('entregado_at')->nullable()->after('entregado_por_user_id');
            $table->string('numero_transferencia', 100)->nullable()->after('entregado_at');
            $table->decimal('monto_depositado', 10, 2)->nullable()->after('numero_transferencia');
            $table->foreignId('sucursal_entrega_id')->nullable()->constrained('sucursales')->nullOnDelete()->after('monto_depositado');
            $table->decimal('limite_credito_anterior', 10, 2)->nullable()->after('sucursal_entrega_id')->comment('Limite credito de distribuidora antes de incremento para regla 50%');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('prestamos', function (Blueprint $table) {
            $table->dropForeign(['entregado_por_user_id']);
            $table->dropForeign(['sucursal_entrega_id']);
            $table->dropColumn([
                'estado_entrega',
                'entregado_por_user_id',
                'entregado_at',
                'numero_transferencia',
                'monto_depositado',
                'sucursal_entrega_id',
                'limite_credito_anterior'
            ]);
        });
    }
};

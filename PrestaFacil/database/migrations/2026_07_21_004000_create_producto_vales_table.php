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
        Schema::create('producto_vales', function (Blueprint $table) {
            $table->id();
            $table->string('clave')->unique();
            $table->string('nombre');
            $table->decimal('monto_prestamo', 10, 2);
            $table->decimal('costo_seguro', 10, 2)->default(0.00);
            $table->integer('plazo_quincenas');
            $table->decimal('comision_apertura', 10, 2)->default(0.00);
            $table->decimal('tasa_interes_quincenal', 5, 2)->default(0.00);
            $table->boolean('activo')->default(true);
            $table->timestamp('desactivado_at')->nullable();
            
            // Usuario que creó y usuario que modificó/desactivó
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by_user_id')->nullable()->constrained('users')->nullOnDelete();

            $table->text('descripcion')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('producto_vales');
    }
};

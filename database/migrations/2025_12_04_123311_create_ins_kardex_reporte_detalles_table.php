<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('ins_kardex_reporte_detalles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reporte_id')->constrained('ins_kardex_reportes')->onDelete('cascade');
            $table->string('codigo_existencia', 50); // índice o código de existencia
            $table->string('nombre_producto', 255);
            $table->string('condicion')->nullable(); // puede ser tipo de producto, estado, etc.
            $table->string('unidad_medida', 50)->nullable(); // tabla 6
            $table->decimal('total_entradas_unidades', 18, 3)->default(0);
            $table->decimal('total_entradas_importe', 18, 2)->default(0);
            $table->decimal('total_salidas_unidades', 18, 3)->default(0);
            $table->decimal('total_salidas_importe', 18, 2)->default(0);
            $table->decimal('saldo_unidades', 18, 3)->default(0);
            $table->decimal('saldo_importe', 18, 2)->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ins_kardex_reporte_detalles');
    }
};

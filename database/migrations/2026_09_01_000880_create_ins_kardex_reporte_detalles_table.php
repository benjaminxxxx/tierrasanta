<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ins_kardex_reporte_detalles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reporte_id')->constrained('ins_kardex_reportes')->cascadeOnDelete();
            $table->foreignId('ins_kardex_id')->nullable()->constrained('ins_kardexes')->nullOnDelete();
            $table->string('grupo_operativo', 50)->nullable();
            $table->string('codigo_existencia', 50);
            $table->string('nombre_producto');
            $table->string('condicion')->nullable();
            $table->string('unidad_medida', 50)->nullable();
            $table->decimal('total_entradas_unidades', 18, 3)->default(0.000);
            $table->decimal('total_entradas_importe', 18, 2)->default(0.00);
            $table->decimal('total_salidas_unidades', 18, 3)->default(0.000);
            $table->decimal('total_salidas_importe', 18, 2)->default(0.00);
            $table->decimal('saldo_unidades', 18, 3)->default(0.000);
            $table->decimal('saldo_importe', 18, 2)->default(0.00);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ins_kardex_reporte_detalles');
    }
};

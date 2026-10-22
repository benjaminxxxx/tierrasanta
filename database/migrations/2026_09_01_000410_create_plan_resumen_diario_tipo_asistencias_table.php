<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plan_resumen_diario_tipo_asistencias', function (Blueprint $table) {
            $table->id();
            $table->foreignId('plan_res_dia_id')->constrained('plan_resumen_diario')->cascadeOnDelete();
            $table->string('codigo', 5);
            $table->string('descripcion');
            $table->string('color', 12);
            $table->integer('horas_jornal')->default(8);
            $table->enum('tipo', ['ASISTENCIA', 'PERMISO', 'LICENCIA', 'VACACIONES'])->default('ASISTENCIA');
            $table->boolean('afecta_sueldo')->default(true);
            $table->decimal('porcentaje_remunerado', 5, 2)->default(100.00);
            $table->boolean('requiere_documento')->default(false);
            $table->boolean('acumula_asistencia')->default(false);
            $table->date('fecha');
            $table->integer('total_asistidos')->default(0);
            $table->timestamps();

            $table->unique(['plan_res_dia_id', 'codigo', 'fecha'], 'resumen_asistencia_unico');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plan_resumen_diario_tipo_asistencias');
    }
};

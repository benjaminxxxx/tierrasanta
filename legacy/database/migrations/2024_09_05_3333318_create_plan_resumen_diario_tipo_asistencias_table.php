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
        Schema::create('plan_resumen_diario_tipo_asistencias', function (Blueprint $table) {
            $table->id();

            // Relación al resumen diario
            $table->foreignId('plan_res_dia_id')
                ->constrained('plan_resumen_diario')
                ->onDelete('cascade');

            // Información copiada del tipo de asistencia original
            $table->string('codigo', 5); // Ej: 'A', 'F', 'DM', etc.
            $table->string('descripcion');
            $table->string('color',12);
            $table->integer('horas_jornal')->default(8);

            $table->enum('tipo', ['ASISTENCIA', 'PERMISO', 'LICENCIA', 'VACACIONES'])
                ->default('ASISTENCIA');

            $table->boolean('afecta_sueldo')->default(true);
            $table->decimal('porcentaje_remunerado', 5, 2)->default(100.00);
            $table->boolean('requiere_documento')->default(false);
            $table->boolean('acumula_asistencia')->default(false);

            // Datos del resumen del día
            $table->date('fecha');
            $table->integer('total_asistidos')->default(0);

            $table->timestamps();

            // Evitar duplicados por día y código
            $table->unique(['plan_res_dia_id', 'codigo', 'fecha'], 'resumen_asistencia_unico');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('plan_resumen_diario_tipo_asistencias');
    }
};

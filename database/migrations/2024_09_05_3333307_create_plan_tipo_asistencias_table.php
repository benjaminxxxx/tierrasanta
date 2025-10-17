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
        Schema::create('plan_tipo_asistencias', function (Blueprint $table) {
            $table->id(); // ID autoincremental en lugar de código como primary para más flexibilidad
            $table->string('codigo', 5)->unique(); // Ej: 'A', 'F', 'AM', 'V'
            $table->string('descripcion'); // Ej: Asistido, Falta, Descanso Médico, etc.
            $table->integer('horas_jornal')->default(8); // Horas a computar o pagar
            $table->string('color', 10)->nullable(); // Color visual (para reportes o UI)

            $table->enum('tipo', ['ASISTENCIA', 'PERMISO', 'LICENCIA', 'VACACIONES'])
                ->default('ASISTENCIA'); // Clasificación general

            $table->boolean('afecta_sueldo')->default(true); // Si reduce o no el pago
            $table->decimal('porcentaje_remunerado', 5, 2)->default(100.00); // Ej: 100, 50, 0

            $table->boolean('requiere_documento')->default(false); // Ej: certificado médico
            $table->boolean('acumula_vacaciones')->default(false); // Si cuenta para vacaciones
            $table->boolean('acumula_asistencia')->default(false); // Si se considera asistencia
            $table->boolean('activo')->default(true); // Para control lógico de catálogo

            $table->timestamps();
        });


    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('plan_tipo_asistencias');
    }
};

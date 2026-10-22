<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plan_tipo_asistencias', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 5);
            $table->string('descripcion');
            $table->integer('horas_jornal')->default(8);
            $table->string('color', 10)->nullable();
            $table->enum('tipo', ['ASISTENCIA', 'PERMISO', 'LICENCIA', 'VACACIONES'])->default('ASISTENCIA');
            $table->boolean('afecta_sueldo')->default(true);
            $table->decimal('porcentaje_remunerado', 5, 2)->default(100.00);
            $table->boolean('requiere_documento')->default(false);
            $table->boolean('acumula_vacaciones')->default(false);
            $table->boolean('acumula_asistencia')->default(false);
            $table->boolean('activo')->default(true);
            $table->foreignId('plan_tipo_suspension_id')->nullable()->constrained('plan_tipos_suspension')->nullOnDelete();
            $table->boolean('sin_suspension')->default(false);
            $table->string('rol', 30)->nullable();
            $table->enum('criterio_bono_asistencia', ['no_afecta', 'afecta', 'revisar'])->default('no_afecta');
            $table->timestamps();

            $table->unique('codigo');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plan_tipo_asistencias');
    }
};

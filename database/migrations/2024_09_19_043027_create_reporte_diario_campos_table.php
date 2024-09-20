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
        Schema::create('reporte_diario_campos', function (Blueprint $table) {
            $table->id();
            $table->date('fecha'); // Campo para la fecha
            $table->integer('campos')->default(1);

            $table->integer('total_planillas_asistidos')->default(0);
            $table->integer('total_faltas')->default(0);
            $table->integer('total_vacaciones')->default(0);
            $table->integer('total_licencia_maternidad')->default(0);
            $table->integer('total_licencia_sin_goce')->default(0);
            $table->integer('total_licencia_con_goce')->default(0);
            $table->integer('total_descanso_medico')->default(0);
            $table->integer('total_atencion_medica')->default(0);
            $table->integer('total_cuadrillas')->default(0);
            $table->integer('total_planilla')->default(0);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reporte_diario_campos');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Un trabajador de planilla que el mismo día figura como cuadrillero (domingo anotado otro día, "grupo tarde" que junta
 * gente de varios grupos…). La justificación es informativa: con ella ese día deja de salir en Tareas pendientes.
 * Una por cuadrillero y fecha (la fecha del registro de cuadrilla).
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('cuad_planilla_justificaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cuadrillero_id')->constrained('cuad_cuadrilleros')->cascadeOnDelete();
            $table->date('fecha')->comment('Fecha del registro de cuadrilla');
            $table->foreignId('plan_empleado_id')->nullable()->constrained('plan_empleados')->nullOnDelete()
                ->comment('Trabajador de planilla con el que se cruzó (si se sabe)');
            $table->string('motivo', 20)->comment('trabajo_extra | otro_dia | otra_persona');
            $table->date('fecha_real')->nullable()->comment('otro_dia: fecha en que realmente se trabajó (p. ej. el domingo)');
            $table->string('nota')->nullable();
            $table->foreignId('creado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['cuadrillero_id', 'fecha']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cuad_planilla_justificaciones');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Actividades (códigos de labor) aceptadas por campo y día. Si un campo tiene más de un código ese día, al guardar
 * horas se pregunta "¿es correcto?"; al aceptar se guardan aquí y no vuelve a preguntar mientras no aparezca uno nuevo.
 * Un código que ya nadie hizo en ese campo ese día se quita solo (CampoActividadProceso::limpiar).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('campo_actividades_aceptadas', function (Blueprint $table) {
            $table->id();
            $table->string('campo_nombre', 50);
            $table->date('fecha');
            $table->integer('codigo_labor');
            $table->foreignId('aceptado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['campo_nombre', 'fecha', 'codigo_labor'], 'campo_act_acep_unico');
            $table->index('fecha');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('campo_actividades_aceptadas');
    }
};

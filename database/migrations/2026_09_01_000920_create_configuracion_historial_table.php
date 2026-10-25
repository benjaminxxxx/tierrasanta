<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Valores con vigencia de los parámetros (RMV, EsSalud, gratificaciones…). Fuente única: las planillas los leen de
 * aquí (valor vigente el día 1 del mes), sin copiarlos. fecha_fin la mantiene el sistema (día antes del siguiente).
 * Un valor que ya usó una planilla generada queda con candado (PlanillaParametroProceso).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('configuracion_historial', function (Blueprint $table) {
            $table->id();
            $table->string('configuracion_codigo');
            $table->decimal('valor', 12, 4);
            $table->date('fecha_inicio');
            $table->date('fecha_fin')->nullable()->comment('Automática: día anterior al siguiente valor');
            $table->boolean('activo')->default(true);
            $table->string('motivo')->nullable()->comment('Por qué cambió: resolución, decreto…');
            $table->foreignId('registrado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->foreign('configuracion_codigo')->references('codigo')->on('configuracion')->cascadeOnDelete();
            $table->unique(['configuracion_codigo', 'fecha_inicio']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('configuracion_historial');
    }
};

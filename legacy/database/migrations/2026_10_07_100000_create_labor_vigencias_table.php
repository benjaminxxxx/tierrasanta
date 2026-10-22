<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Historia de los códigos de labor. Los registros (planilla, cuadrilla, bonos, costos) guardan solo el código;
 * cuando un código se reutiliza para otra labor desde una fecha, lo que significaba antes queda aquí con su
 * rango de fechas. Así los registros antiguos se siguen leyendo con la labor de su momento.
 *
 * labores sigue teniendo una fila por código: su significado actual, vigente desde labores.vigente_desde
 * (null = desde siempre).
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('labor_vigencias', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('codigo');
            $table->string('nombre_labor', 255);
            $table->string('codigo_mano_obra', 255)->nullable();
            $table->string('tipo_asistencia_codigo', 10)->nullable();
            $table->string('unidades', 20)->nullable();
            $table->date('desde')->nullable()->comment('null = desde siempre');
            $table->date('hasta');
            $table->string('motivo', 500)->nullable()->comment('Por qué se reasignó el código');
            $table->foreignId('creado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['codigo', 'hasta']);
        });

        Schema::table('labores', function (Blueprint $table) {
            $table->date('vigente_desde')->nullable()->after('codigo')->comment('Desde cuándo el código significa esta labor (null = siempre)');
        });
    }

    public function down(): void
    {
        Schema::table('labores', fn(Blueprint $table) => $table->dropColumn('vigente_desde'));
        Schema::dropIfExists('labor_vigencias');
    }
};

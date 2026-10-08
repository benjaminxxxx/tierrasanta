<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * BDD de costos: trabajador de planilla de cada fila (el nombre no sirve para cuadrar: "HUARACALLO" vs
 * "HUARACCALLO", "ALBERTO G." vs "ALBERTO GILBERTO") e índices por tipo y fecha (las regeneraciones borran
 * por origen_tipo + rango de fechas).
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('resumen_costo_diarios', function (Blueprint $table) {
            $table->unsignedBigInteger('plan_empleado_id')->nullable()->after('trabajador');
            $table->index(['origen_tipo', 'fecha'], 'resumen_costo_origen_fecha_index');
            $table->index(['plan_empleado_id', 'fecha'], 'resumen_costo_empleado_fecha_index');
            $table->index(['campo', 'fecha'], 'resumen_costo_campo_fecha_index');
        });
    }

    public function down(): void
    {
        Schema::table('resumen_costo_diarios', function (Blueprint $table) {
            $table->dropIndex('resumen_costo_origen_fecha_index');
            $table->dropIndex('resumen_costo_empleado_fecha_index');
            $table->dropIndex('resumen_costo_campo_fecha_index');
            $table->dropColumn('plan_empleado_id');
        });
    }
};

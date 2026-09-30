<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Retira la vista v_reporte_actividades_diario: solo la usaba el bloque de actividades del Reporte Diario
 * (movido a legacy/) y era muy lenta (minutos por consulta, bloqueaba la tabla actividades).
 * Su definición se conserva en legacy/database/views/v_reporte_actividades_diario.sql y `down` la recrea.
 * La tabla rep_actividades_diarias no se toca.
 */
return new class extends Migration {
    public function up(): void
    {
        DB::statement('DROP VIEW IF EXISTS v_reporte_actividades_diario');
    }

    public function down(): void
    {
        DB::statement(file_get_contents(base_path('legacy/database/views/v_reporte_actividades_diario.sql')));
    }
};

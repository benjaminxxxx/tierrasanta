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
        DB::statement("
        CREATE OR REPLACE VIEW v_reporte_actividades_diario AS
        SELECT
            a.id                                    AS actividad_id,
            a.fecha,
            a.campo,
            a.codigo_labor,
            a.nombre_labor,
            a.unidades,
            a.recojos,
            COUNT(DISTINCT am.id)                   AS total_metodos,
            COUNT(DISTINCT pdh.plan_reg_dia_id)     AS total_planilla,
            COUNT(DISTINCT cdh.registro_diario_id)  AS total_cuadrilla
        FROM actividades a
        LEFT JOIN actividad_metodos am
            ON am.actividad_id = a.id
        LEFT JOIN plan_detalles_horas pdh
            ON  pdh.campo_nombre = a.campo
            AND pdh.codigo_labor  = a.codigo_labor
            AND EXISTS (
                SELECT 1 FROM plan_registros_diarios prd
                WHERE prd.id    = pdh.plan_reg_dia_id
                  AND prd.fecha = a.fecha
            )
        LEFT JOIN cuad_detalles_horas cdh
            ON  cdh.campo_nombre = a.campo
            AND cdh.codigo_labor  = a.codigo_labor
            AND EXISTS (
                SELECT 1 FROM cuad_registros_diarios crd
                WHERE crd.id    = cdh.registro_diario_id
                  AND crd.fecha = a.fecha
            )
        GROUP BY
            a.id, a.fecha, a.campo, a.codigo_labor,
            a.nombre_labor, a.unidades, a.recojos
    ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
       DB::statement('DROP VIEW IF EXISTS v_reporte_actividades_diario');
    }
};

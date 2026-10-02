<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Horas de jornada ordinaria que se declaran en el PLAME: las horas registradas menos 8 h por cada día de
 * suspensión que tiene horas (descanso médico, licencia con goce…). Esos días se pagan, pero no se trabajaron.
 *
 * plame_total_horas sigue siendo el total registrado: con él se calculan el jornal básico (0121) y lo que
 * se le paga al trabajador.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('plan_mensual_personals', function (Blueprint $table) {
            $table->decimal('plame_horas_jornada', 7, 2)->nullable()->after('plame_total_horas')
                ->comment('Horas de jornada ordinaria del PLAME (sin los días de suspensión)');
        });

        // Planillas ya generadas
        DB::statement("
            UPDATE plan_mensual_personals p
            JOIN (
                SELECT md.plan_mensual_id, md.plan_empleado_id,
                    SUM(r.total_horas - CASE WHEN t.plan_tipo_suspension_id IS NOT NULL THEN LEAST(8, r.total_horas) ELSE 0 END) AS horas
                FROM plan_registros_diarios r
                JOIN plan_mensual_detalles md ON md.id = r.plan_det_men_id
                LEFT JOIN plan_tipo_asistencias t ON t.codigo = r.asistencia
                GROUP BY md.plan_mensual_id, md.plan_empleado_id
            ) h ON h.plan_mensual_id = p.plan_mensual_id AND h.plan_empleado_id = p.plan_empleado_id
            SET p.plame_horas_jornada = h.horas
        ");
    }

    public function down(): void
    {
        Schema::table('plan_mensual_personals', fn(Blueprint $table) => $table->dropColumn('plame_horas_jornada'));
    }
};

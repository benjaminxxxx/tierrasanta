<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Método destajo "respeta el jornal": el trabajador cobra su jornal completo y el bono es solo lo que el destajo
 * supera a ese jornal (si no lo supera, bono 0). Esas horas ya no se descuentan del jornal, así que los triggers de
 * horas_destajo solo cuentan los destajos antiguos (sin estándar y sin este flag).
 */
return new class extends Migration
{
    /** Triggers que calculan horas_destajo: [nombre, tabla, evento, registro(s) a recalcular] */
    private const TRIGGERS = [
        ['tr_recalcular_horas_destajo_insert', 'cuad_bonos_actividades', 'INSERT', ['NEW']],
        ['tr_recalcular_horas_destajo_update', 'cuad_bonos_actividades', 'UPDATE', ['NEW', 'OLD']],
        ['tr_recalcular_horas_destajo_delete', 'cuad_bonos_actividades', 'DELETE', ['OLD']],
        ['tr_detalle_horas_insert_destajo', 'cuad_detalles_horas', 'INSERT', ['NEW']],
        ['tr_detalle_horas_update_destajo', 'cuad_detalles_horas', 'UPDATE', ['NEW']],
    ];

    public function up(): void
    {
        if (!Schema::hasColumn('actividad_metodos', 'respeta_jornal')) {
            Schema::table('actividad_metodos', function (Blueprint $table) {
                $table->boolean('respeta_jornal')->default(false)->after('estandar');
            });
        }
        $this->crearTriggers('AND am.estandar IS NULL AND am.respeta_jornal = 0');
    }

    public function down(): void
    {
        $this->crearTriggers('AND am.estandar IS NULL');
        if (Schema::hasColumn('actividad_metodos', 'respeta_jornal')) {
            Schema::table('actividad_metodos', fn(Blueprint $table) => $table->dropColumn('respeta_jornal'));
        }
    }

    private function crearTriggers(string $condicion): void
    {
        foreach (self::TRIGGERS as [$nombre, $tabla, $evento, $registros]) {
            DB::unprepared("DROP TRIGGER IF EXISTS `{$nombre}`");
            $cuerpo = '';
            foreach ($registros as $i => $reg) {
                $recalculo = $this->recalculo($reg, $condicion);
                // En UPDATE de bonos, el registro anterior solo si cambió de registro diario
                $cuerpo .= $i > 0 ? "IF OLD.registro_diario_id <> NEW.registro_diario_id THEN\n{$recalculo}\nEND IF;\n" : "{$recalculo}\n";
            }
            DB::unprepared("CREATE TRIGGER `{$nombre}` AFTER {$evento} ON `{$tabla}` FOR EACH ROW\nBEGIN\nDECLARE v_horas DECIMAL(5,2);\n{$cuerpo}END");
        }
    }

    private function recalculo(string $reg, string $condicion): string
    {
        return <<<SQL
            SELECT IFNULL(SUM(TIMESTAMPDIFF(MINUTE, dh.hora_inicio, dh.hora_fin) / 60.0), 0) INTO v_horas
            FROM cuad_bonos_actividades ba
            INNER JOIN actividad_metodos am ON am.id = ba.metodo_id
            INNER JOIN actividades a ON a.id = ba.actividad_id
            INNER JOIN cuad_detalles_horas dh ON dh.registro_diario_id = ba.registro_diario_id AND dh.codigo_labor = a.codigo_labor
            WHERE ba.registro_diario_id = {$reg}.registro_diario_id
              AND ba.metodo_id IS NOT NULL
              {$condicion};
            UPDATE cuad_registros_diarios SET horas_destajo = v_horas WHERE id = {$reg}.registro_diario_id;
            SQL;
    }
};

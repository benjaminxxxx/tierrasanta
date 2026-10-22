<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cuad_detalles_horas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('registro_diario_id')->constrained('cuad_registros_diarios')->cascadeOnDelete();
            $table->string('campo_nombre');
            $table->integer('codigo_labor')->nullable();
            $table->time('hora_inicio');
            $table->time('hora_fin');
            $table->timestamps();
        });

        // Triggers (las tablas que usan se resuelven al ejecutarse, no al crearse)
        DB::unprepared(<<<'SQL'
CREATE TRIGGER `tr_detalle_horas_insert_destajo` AFTER INSERT ON `cuad_detalles_horas` FOR EACH ROW
BEGIN
                DECLARE v_horas DECIMAL(5,2);

                SELECT IFNULL(SUM(
                    TIMESTAMPDIFF(MINUTE, dh.hora_inicio, dh.hora_fin) / 60.0
                ), 0) INTO v_horas
                FROM cuad_bonos_actividades ba
                INNER JOIN actividad_metodos am
                    ON am.id = ba.metodo_id
                INNER JOIN actividades a
                    ON a.id = ba.actividad_id
                INNER JOIN cuad_detalles_horas dh
                    ON dh.registro_diario_id = ba.registro_diario_id
                    AND dh.codigo_labor = a.codigo_labor
                WHERE ba.registro_diario_id = NEW.registro_diario_id
                  AND ba.metodo_id IS NOT NULL
                  AND am.estandar IS NULL;

                UPDATE cuad_registros_diarios
                SET horas_destajo = v_horas
                WHERE id = NEW.registro_diario_id;
            END
SQL);
        DB::unprepared(<<<'SQL'
CREATE TRIGGER `tr_detalle_horas_update_destajo` AFTER UPDATE ON `cuad_detalles_horas` FOR EACH ROW
BEGIN
                DECLARE v_horas DECIMAL(5,2);

                SELECT IFNULL(SUM(
                    TIMESTAMPDIFF(MINUTE, dh.hora_inicio, dh.hora_fin) / 60.0
                ), 0) INTO v_horas
                FROM cuad_bonos_actividades ba
                INNER JOIN actividad_metodos am
                    ON am.id = ba.metodo_id
                INNER JOIN actividades a
                    ON a.id = ba.actividad_id
                INNER JOIN cuad_detalles_horas dh
                    ON dh.registro_diario_id = ba.registro_diario_id
                    AND dh.codigo_labor = a.codigo_labor
                WHERE ba.registro_diario_id = NEW.registro_diario_id
                  AND ba.metodo_id IS NOT NULL
                  AND am.estandar IS NULL;

                UPDATE cuad_registros_diarios
                SET horas_destajo = v_horas
                WHERE id = NEW.registro_diario_id;
            END
SQL);
    }

    public function down(): void
    {
        DB::unprepared('DROP TRIGGER IF EXISTS `tr_detalle_horas_insert_destajo`');
        DB::unprepared('DROP TRIGGER IF EXISTS `tr_detalle_horas_update_destajo`');
        Schema::dropIfExists('cuad_detalles_horas');
    }
};

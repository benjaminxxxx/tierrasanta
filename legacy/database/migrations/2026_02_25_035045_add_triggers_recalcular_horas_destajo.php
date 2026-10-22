<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // ─── DROP TRIGGERS ANTERIORES ────────────────────────────────────────
        DB::unprepared("DROP TRIGGER IF EXISTS tr_recalcular_horas_destajo_insert");
        DB::unprepared("DROP TRIGGER IF EXISTS tr_recalcular_horas_destajo_update");
        DB::unprepared("DROP TRIGGER IF EXISTS tr_recalcular_horas_destajo_delete");
        DB::unprepared("DROP TRIGGER IF EXISTS tr_detalle_horas_update_destajo");
        DB::unprepared("DROP TRIGGER IF EXISTS tr_detalle_horas_update_after");

        // ─── TRIGGER 1: INSERT en cuad_bonos_actividades ─────────────────────
        DB::unprepared("
            CREATE TRIGGER tr_recalcular_horas_destajo_insert
            AFTER INSERT ON cuad_bonos_actividades
            FOR EACH ROW
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
                  AND am.estandar IS NULL;  -- solo destajo

                UPDATE cuad_registros_diarios
                SET horas_destajo = v_horas
                WHERE id = NEW.registro_diario_id;
            END
        ");

        // ─── TRIGGER 2: UPDATE en cuad_bonos_actividades ─────────────────────
        DB::unprepared("
            CREATE TRIGGER tr_recalcular_horas_destajo_update
            AFTER UPDATE ON cuad_bonos_actividades
            FOR EACH ROW
            BEGIN
                DECLARE v_horas DECIMAL(5,2);

                -- Recalcular para el registro nuevo
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

                -- Si cambió de registro_diario, recalcular también el anterior
                IF OLD.registro_diario_id <> NEW.registro_diario_id THEN
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
                    WHERE ba.registro_diario_id = OLD.registro_diario_id
                      AND ba.metodo_id IS NOT NULL
                      AND am.estandar IS NULL;

                    UPDATE cuad_registros_diarios
                    SET horas_destajo = v_horas
                    WHERE id = OLD.registro_diario_id;
                END IF;
            END
        ");

        // ─── TRIGGER 3: DELETE en cuad_bonos_actividades ─────────────────────
        DB::unprepared("
            CREATE TRIGGER tr_recalcular_horas_destajo_delete
            AFTER DELETE ON cuad_bonos_actividades
            FOR EACH ROW
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
                WHERE ba.registro_diario_id = OLD.registro_diario_id
                  AND ba.metodo_id IS NOT NULL
                  AND am.estandar IS NULL;

                UPDATE cuad_registros_diarios
                SET horas_destajo = v_horas
                WHERE id = OLD.registro_diario_id;
            END
        ");

        // ─── TRIGGER 4: INSERT en cuad_detalles_horas ────────────────────────
        DB::unprepared("
            CREATE TRIGGER tr_detalle_horas_insert_destajo
            AFTER INSERT ON cuad_detalles_horas
            FOR EACH ROW
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
        ");

        // ─── TRIGGER 5: UPDATE en cuad_detalles_horas ────────────────────────
        DB::unprepared("
            CREATE TRIGGER tr_detalle_horas_update_destajo
            AFTER UPDATE ON cuad_detalles_horas
            FOR EACH ROW
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
        ");
    }

    public function down(): void
    {
        DB::unprepared("DROP TRIGGER IF EXISTS tr_recalcular_horas_destajo_insert");
        DB::unprepared("DROP TRIGGER IF EXISTS tr_recalcular_horas_destajo_update");
        DB::unprepared("DROP TRIGGER IF EXISTS tr_recalcular_horas_destajo_delete");
        DB::unprepared("DROP TRIGGER IF EXISTS tr_detalle_horas_insert_destajo");
        DB::unprepared("DROP TRIGGER IF EXISTS tr_detalle_horas_update_destajo");
    }
};

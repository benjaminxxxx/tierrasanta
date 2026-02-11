<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // TRIGGER 1: Después de INSERTAR un bono
        DB::unprepared("
            DROP TRIGGER IF EXISTS tr_recalcular_horas_destajo_insert;
        ");

        DB::unprepared("
            CREATE TRIGGER tr_recalcular_horas_destajo_insert
            AFTER INSERT ON cuad_bonos_actividades
            FOR EACH ROW
            BEGIN
                DECLARE total_horas_destajo DECIMAL(5,2);
                
                -- Calcular total de horas a destajo para este registro diario
                -- Solo cuenta horas de actividades con tramos pero SIN estándar
                SELECT IFNULL(SUM(
                    TIMESTAMPDIFF(MINUTE, dh.hora_inicio, dh.hora_fin) / 60.0
                ), 0) INTO total_horas_destajo
                FROM cuad_bonos_actividades ba
                INNER JOIN actividades a ON ba.actividad_id = a.id
                INNER JOIN cuad_detalles_horas dh 
                    ON dh.registro_diario_id = ba.registro_diario_id
                    AND dh.codigo_labor = a.codigo_labor
                WHERE ba.registro_diario_id = NEW.registro_diario_id
                  AND a.tramos_bonificacion IS NOT NULL
                  AND (a.estandar_produccion IS NULL OR a.estandar_produccion = 0);
                
                -- Actualizar el registro diario
                UPDATE cuad_registros_diarios
                SET horas_destajo = total_horas_destajo
                WHERE id = NEW.registro_diario_id;
            END
        ");

        // TRIGGER 2: Después de ACTUALIZAR un bono
        DB::unprepared("
            DROP TRIGGER IF EXISTS tr_recalcular_horas_destajo_update;
        ");

        DB::unprepared("
            CREATE TRIGGER tr_recalcular_horas_destajo_update
            AFTER UPDATE ON cuad_bonos_actividades
            FOR EACH ROW
            BEGIN
                DECLARE total_horas_destajo DECIMAL(5,2);
                
                SELECT IFNULL(SUM(
                    TIMESTAMPDIFF(MINUTE, dh.hora_inicio, dh.hora_fin) / 60.0
                ), 0) INTO total_horas_destajo
                FROM cuad_bonos_actividades ba
                INNER JOIN actividades a ON ba.actividad_id = a.id
                INNER JOIN cuad_detalles_horas dh 
                    ON dh.registro_diario_id = ba.registro_diario_id
                    AND dh.codigo_labor = a.codigo_labor
                WHERE ba.registro_diario_id = NEW.registro_diario_id
                  AND a.tramos_bonificacion IS NOT NULL
                  AND (a.estandar_produccion IS NULL OR a.estandar_produccion = 0);
                
                UPDATE cuad_registros_diarios
                SET horas_destajo = total_horas_destajo
                WHERE id = NEW.registro_diario_id;
                
                -- Si cambió de registro_diario, recalcular también el antiguo
                IF OLD.registro_diario_id <> NEW.registro_diario_id THEN
                    SELECT IFNULL(SUM(
                        TIMESTAMPDIFF(MINUTE, dh.hora_inicio, dh.hora_fin) / 60.0
                    ), 0) INTO total_horas_destajo
                    FROM cuad_bonos_actividades ba
                    INNER JOIN actividades a ON ba.actividad_id = a.id
                    INNER JOIN cuad_detalles_horas dh 
                        ON dh.registro_diario_id = ba.registro_diario_id
                        AND dh.codigo_labor = a.codigo_labor
                    WHERE ba.registro_diario_id = OLD.registro_diario_id
                      AND a.tramos_bonificacion IS NOT NULL
                      AND (a.estandar_produccion IS NULL OR a.estandar_produccion = 0);
                    
                    UPDATE cuad_registros_diarios
                    SET horas_destajo = total_horas_destajo
                    WHERE id = OLD.registro_diario_id;
                END IF;
            END
        ");

        // TRIGGER 3: Después de ELIMINAR un bono
        DB::unprepared("
            DROP TRIGGER IF EXISTS tr_recalcular_horas_destajo_delete;
        ");

        DB::unprepared("
            CREATE TRIGGER tr_recalcular_horas_destajo_delete
            AFTER DELETE ON cuad_bonos_actividades
            FOR EACH ROW
            BEGIN
                DECLARE total_horas_destajo DECIMAL(5,2);
                
                SELECT IFNULL(SUM(
                    TIMESTAMPDIFF(MINUTE, dh.hora_inicio, dh.hora_fin) / 60.0
                ), 0) INTO total_horas_destajo
                FROM cuad_bonos_actividades ba
                INNER JOIN actividades a ON ba.actividad_id = a.id
                INNER JOIN cuad_detalles_horas dh 
                    ON dh.registro_diario_id = ba.registro_diario_id
                    AND dh.codigo_labor = a.codigo_labor
                WHERE ba.registro_diario_id = OLD.registro_diario_id
                  AND a.tramos_bonificacion IS NOT NULL
                  AND (a.estandar_produccion IS NULL OR a.estandar_produccion = 0);
                
                UPDATE cuad_registros_diarios
                SET horas_destajo = total_horas_destajo
                WHERE id = OLD.registro_diario_id;
            END
        ");

        // TRIGGER 4: Cuando se actualiza/inserta detalle de horas
        DB::unprepared("
            DROP TRIGGER IF EXISTS tr_detalle_horas_update_destajo;
        ");

        DB::unprepared("
            CREATE TRIGGER tr_detalle_horas_update_destajo
            AFTER INSERT ON cuad_detalles_horas
            FOR EACH ROW
            BEGIN
                DECLARE total_horas_destajo DECIMAL(5,2);
                
                SELECT IFNULL(SUM(
                    TIMESTAMPDIFF(MINUTE, dh.hora_inicio, dh.hora_fin) / 60.0
                ), 0) INTO total_horas_destajo
                FROM cuad_bonos_actividades ba
                INNER JOIN actividades a ON ba.actividad_id = a.id
                INNER JOIN cuad_detalles_horas dh 
                    ON dh.registro_diario_id = ba.registro_diario_id
                    AND dh.codigo_labor = a.codigo_labor
                WHERE ba.registro_diario_id = NEW.registro_diario_id
                  AND a.tramos_bonificacion IS NOT NULL
                  AND (a.estandar_produccion IS NULL OR a.estandar_produccion = 0);
                
                UPDATE cuad_registros_diarios
                SET horas_destajo = total_horas_destajo
                WHERE id = NEW.registro_diario_id;
            END
        ");

        DB::unprepared("
            DROP TRIGGER IF EXISTS tr_detalle_horas_update_after;
        ");

        DB::unprepared("
            CREATE TRIGGER tr_detalle_horas_update_after
            AFTER UPDATE ON cuad_detalles_horas
            FOR EACH ROW
            BEGIN
                DECLARE total_horas_destajo DECIMAL(5,2);
                
                SELECT IFNULL(SUM(
                    TIMESTAMPDIFF(MINUTE, dh.hora_inicio, dh.hora_fin) / 60.0
                ), 0) INTO total_horas_destajo
                FROM cuad_bonos_actividades ba
                INNER JOIN actividades a ON ba.actividad_id = a.id
                INNER JOIN cuad_detalles_horas dh 
                    ON dh.registro_diario_id = ba.registro_diario_id
                    AND dh.codigo_labor = a.codigo_labor
                WHERE ba.registro_diario_id = NEW.registro_diario_id
                  AND a.tramos_bonificacion IS NOT NULL
                  AND (a.estandar_produccion IS NULL OR a.estandar_produccion = 0);
                
                UPDATE cuad_registros_diarios
                SET horas_destajo = total_horas_destajo
                WHERE id = NEW.registro_diario_id;
            END
        ");
    }

    public function down(): void
    {
        DB::unprepared("DROP TRIGGER IF EXISTS tr_recalcular_horas_destajo_insert");
        DB::unprepared("DROP TRIGGER IF EXISTS tr_recalcular_horas_destajo_update");
        DB::unprepared("DROP TRIGGER IF EXISTS tr_recalcular_horas_destajo_delete");
        DB::unprepared("DROP TRIGGER IF EXISTS tr_detalle_horas_update_destajo");
        DB::unprepared("DROP TRIGGER IF EXISTS tr_detalle_horas_update_after");

    }
};

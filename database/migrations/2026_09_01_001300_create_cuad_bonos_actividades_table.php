<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cuad_bonos_actividades', function (Blueprint $table) {
            $table->id();
            $table->foreignId('registro_diario_id')->constrained('cuad_registros_diarios')->cascadeOnDelete();
            $table->foreignId('actividad_id')->constrained('actividades')->cascadeOnDelete();
            $table->decimal('total_bono', 10, 2)->default(0.00);
            $table->boolean('bono_manual')->default(false);
            $table->boolean('se_paga_con_jornal')->default(true)->comment('true = junto con el jornal, false = se acumula');
            $table->boolean('esta_pagado')->default(false);
            $table->foreignId('desglose_detalle_id')->nullable()->constrained('desglose_detalles')->nullOnDelete();
            $table->timestamps();
            $table->foreignId('metodo_id')->nullable()->constrained('actividad_metodos')->nullOnDelete();

            $table->unique(['registro_diario_id', 'actividad_id'], 'registro_actividad_unique');
        });

        // Triggers (las tablas que usan se resuelven al ejecutarse, no al crearse)
        DB::unprepared(<<<'SQL'
CREATE TRIGGER `tr_recalcular_horas_destajo_insert` AFTER INSERT ON `cuad_bonos_actividades` FOR EACH ROW
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
SQL);
        DB::unprepared(<<<'SQL'
CREATE TRIGGER `tr_recalcular_horas_destajo_update` AFTER UPDATE ON `cuad_bonos_actividades` FOR EACH ROW
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
SQL);
        DB::unprepared(<<<'SQL'
CREATE TRIGGER `tr_recalcular_horas_destajo_delete` AFTER DELETE ON `cuad_bonos_actividades` FOR EACH ROW
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
SQL);
    }

    public function down(): void
    {
        DB::unprepared('DROP TRIGGER IF EXISTS `tr_recalcular_horas_destajo_insert`');
        DB::unprepared('DROP TRIGGER IF EXISTS `tr_recalcular_horas_destajo_update`');
        DB::unprepared('DROP TRIGGER IF EXISTS `tr_recalcular_horas_destajo_delete`');
        Schema::dropIfExists('cuad_bonos_actividades');
    }
};

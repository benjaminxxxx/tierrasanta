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
        // DROP defensivo
        DB::unprepared('DROP TRIGGER IF EXISTS trg_cochinilla_ingresos_vent_i');
        DB::unprepared('DROP TRIGGER IF EXISTS trg_cochinilla_ingresos_vent_u');
        DB::unprepared('DROP TRIGGER IF EXISTS trg_cochinilla_venteados_bi');
        DB::unprepared('DROP TRIGGER IF EXISTS trg_cochinilla_venteados_bu');

        /* AFTER INSERT en ingresos */
        DB::unprepared("
        CREATE TRIGGER trg_cochinilla_ingresos_vent_i
        AFTER INSERT ON cochinilla_ingresos
        FOR EACH ROW
        BEGIN
            UPDATE cochinilla_venteados
            SET cochinilla_ingreso_id = NEW.id
            WHERE lote = NEW.lote
              AND cochinilla_ingreso_id IS NULL;
        END
    ");

        /* AFTER UPDATE en ingresos */
        DB::unprepared("
        CREATE TRIGGER trg_cochinilla_ingresos_vent_u
        AFTER UPDATE ON cochinilla_ingresos
        FOR EACH ROW
        BEGIN
            IF NEW.lote <> OLD.lote THEN

                /* 1. Liberar venteados del lote antiguo */
                UPDATE cochinilla_venteados
                SET cochinilla_ingreso_id = NULL
                WHERE cochinilla_ingreso_id = OLD.id;

                /* 2. Asignar venteados del nuevo lote */
                UPDATE cochinilla_venteados
                SET cochinilla_ingreso_id = NEW.id
                WHERE lote = NEW.lote;

            END IF;
        END
    ");

        /* BEFORE INSERT en venteados */
        DB::unprepared("
        CREATE TRIGGER trg_cochinilla_venteados_bi
        BEFORE INSERT ON cochinilla_venteados
        FOR EACH ROW
        BEGIN
            DECLARE v_ingreso_id BIGINT;

            SELECT id
            INTO v_ingreso_id
            FROM cochinilla_ingresos
            WHERE lote = NEW.lote
            LIMIT 1;

            SET NEW.cochinilla_ingreso_id = v_ingreso_id;
        END
    ");

        /* BEFORE UPDATE en venteados */
        DB::unprepared("
        CREATE TRIGGER trg_cochinilla_venteados_bu
        BEFORE UPDATE ON cochinilla_venteados
        FOR EACH ROW
        BEGIN
            DECLARE v_ingreso_id BIGINT;

            IF NEW.lote <> OLD.lote THEN

                SELECT id
                INTO v_ingreso_id
                FROM cochinilla_ingresos
                WHERE lote = NEW.lote
                LIMIT 1;

                SET NEW.cochinilla_ingreso_id = v_ingreso_id;

            END IF;
        END
    ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::unprepared('DROP TRIGGER IF EXISTS trg_cochinilla_ingresos_vent_i');
        DB::unprepared('DROP TRIGGER IF EXISTS trg_cochinilla_ingresos_vent_u');
        DB::unprepared('DROP TRIGGER IF EXISTS trg_cochinilla_venteados_bi');
        DB::unprepared('DROP TRIGGER IF EXISTS trg_cochinilla_venteados_bu');
    }
};

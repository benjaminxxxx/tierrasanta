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
        DB::unprepared('DROP TRIGGER IF EXISTS trg_cochinilla_ingresos_filt_i');
        DB::unprepared('DROP TRIGGER IF EXISTS trg_cochinilla_ingresos_filt_u');
        DB::unprepared('DROP TRIGGER IF EXISTS trg_cochinilla_filtrados_bi');
        DB::unprepared('DROP TRIGGER IF EXISTS trg_cochinilla_filtrados_bu');

        DB::unprepared("
        CREATE TRIGGER trg_cochinilla_ingresos_filt_i
        AFTER INSERT ON cochinilla_ingresos
        FOR EACH ROW
        BEGIN
            UPDATE cochinilla_filtrados
            SET cochinilla_ingreso_id = NEW.id
            WHERE lote = NEW.lote
              AND cochinilla_ingreso_id IS NULL;
        END
    ");
        /* AFTER UPDATE */
        DB::unprepared("
        CREATE TRIGGER trg_cochinilla_ingresos_filt_u
        AFTER UPDATE ON cochinilla_ingresos
        FOR EACH ROW
        BEGIN
            IF NEW.lote <> OLD.lote THEN

                /* 1. Liberar filtrados del lote antiguo */
                UPDATE cochinilla_filtrados
                SET cochinilla_ingreso_id = NULL
                WHERE cochinilla_ingreso_id = OLD.id;

                /* 2. Asignar filtrados del nuevo lote */
                UPDATE cochinilla_filtrados
                SET cochinilla_ingreso_id = NEW.id
                WHERE lote = NEW.lote;

            END IF;
        END
    ");

        /* BEFORE INSERT */
        DB::unprepared("
        CREATE TRIGGER trg_cochinilla_filtrados_bi
        BEFORE INSERT ON cochinilla_filtrados
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
        /* BEFORE UPDATE */
        DB::unprepared("
        CREATE TRIGGER trg_cochinilla_filtrados_bu
        BEFORE UPDATE ON cochinilla_filtrados
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
        DB::unprepared('DROP TRIGGER IF EXISTS trg_cochinilla_ingresos_filt_i');
        DB::unprepared('DROP TRIGGER IF EXISTS trg_cochinilla_ingresos_filt_u');
        DB::unprepared('DROP TRIGGER IF EXISTS trg_cochinilla_filtrados_bi');
        DB::unprepared('DROP TRIGGER IF EXISTS trg_cochinilla_filtrados_bu');
    }
};

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
        DB::unprepared('DROP TRIGGER IF EXISTS trg_cochinilla_ingresos_bi');
        DB::unprepared('DROP TRIGGER IF EXISTS trg_cochinilla_ingresos_bu');

        DB::unprepared("
            CREATE TRIGGER trg_cochinilla_ingresos_bi
            BEFORE INSERT ON cochinilla_ingresos
            FOR EACH ROW
            BEGIN
                DECLARE v_campania_id BIGINT;

                SELECT id
                INTO v_campania_id
                FROM campos_campanias
                WHERE campo = NEW.campo
                  AND fecha_inicio <= NEW.fecha
                  AND (fecha_fin IS NULL OR fecha_fin >= NEW.fecha)
                ORDER BY fecha_inicio DESC
                LIMIT 1;

                SET NEW.campo_campania_id = v_campania_id;
            END
        ");

        DB::unprepared("
            CREATE TRIGGER trg_cochinilla_ingresos_bu
            BEFORE UPDATE ON cochinilla_ingresos
            FOR EACH ROW
            BEGIN
                DECLARE v_campania_id BIGINT;

                IF NEW.campo <> OLD.campo OR NEW.fecha <> OLD.fecha THEN
                    SELECT id
                    INTO v_campania_id
                    FROM campos_campanias
                    WHERE campo = NEW.campo
                      AND fecha_inicio <= NEW.fecha
                      AND (fecha_fin IS NULL OR fecha_fin >= NEW.fecha)
                    ORDER BY fecha_inicio DESC
                    LIMIT 1;

                    SET NEW.campo_campania_id = v_campania_id;
                END IF;
            END
        ");
    }

    public function down(): void
    {
        DB::unprepared('DROP TRIGGER IF EXISTS trg_cochinilla_ingresos_bi');
        DB::unprepared('DROP TRIGGER IF EXISTS trg_cochinilla_ingresos_bu');
    }
};

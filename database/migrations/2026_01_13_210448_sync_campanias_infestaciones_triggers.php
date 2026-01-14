<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        /* DROP defensivo */
        DB::unprepared('DROP TRIGGER IF EXISTS trg_campanias_sync_infestaciones_u');
        DB::unprepared('DROP TRIGGER IF EXISTS trg_infestaciones_bi');
        DB::unprepared('DROP TRIGGER IF EXISTS trg_infestaciones_bu');

        /*
        |--------------------------------------------------------------------------
        | AFTER UPDATE en campañas
        |--------------------------------------------------------------------------
        | Re-sincroniza todas las infestaciones del campo
        */
        DB::unprepared("
        CREATE TRIGGER trg_campanias_sync_infestaciones_u
        AFTER UPDATE ON campos_campanias
        FOR EACH ROW
        BEGIN
            /* 1. Liberar infestaciones del campo */
            UPDATE cochinilla_infestaciones
            SET campo_campania_id = NULL
            WHERE campo_nombre = NEW.campo
              AND campo_campania_id = OLD.id;

            /* 2. Reasignar infestaciones según nuevo rango */
            UPDATE cochinilla_infestaciones i
            SET campo_campania_id = NEW.id
            WHERE i.campo_nombre = NEW.campo
              AND i.fecha >= NEW.fecha_inicio
              AND (
                    NEW.fecha_fin IS NULL
                    OR i.fecha <= NEW.fecha_fin
                  );
        END
        ");

        /*
        |--------------------------------------------------------------------------
        | BEFORE INSERT en infestaciones
        |--------------------------------------------------------------------------
        */
        DB::unprepared("
        CREATE TRIGGER trg_infestaciones_bi
        BEFORE INSERT ON cochinilla_infestaciones
        FOR EACH ROW
        BEGIN
            DECLARE v_campania_id BIGINT;

            SELECT id
            INTO v_campania_id
            FROM campos_campanias
            WHERE campo = NEW.campo_nombre
              AND fecha_inicio <= NEW.fecha
              AND (
                    fecha_fin IS NULL
                    OR NEW.fecha <= fecha_fin
                  )
            ORDER BY fecha_inicio DESC
            LIMIT 1;

            SET NEW.campo_campania_id = v_campania_id;
        END
        ");

        /*
        |--------------------------------------------------------------------------
        | BEFORE UPDATE en infestaciones
        |--------------------------------------------------------------------------
        */
        DB::unprepared("
        CREATE TRIGGER trg_infestaciones_bu
        BEFORE UPDATE ON cochinilla_infestaciones
        FOR EACH ROW
        BEGIN
            DECLARE v_campania_id BIGINT;

            IF NEW.fecha <> OLD.fecha
               OR NEW.campo_nombre <> OLD.campo_nombre THEN

                SELECT id
                INTO v_campania_id
                FROM campos_campanias
                WHERE campo = NEW.campo_nombre
                  AND fecha_inicio <= NEW.fecha
                  AND (
                        fecha_fin IS NULL
                        OR NEW.fecha <= fecha_fin
                      )
                ORDER BY fecha_inicio DESC
                LIMIT 1;

                SET NEW.campo_campania_id = v_campania_id;

            END IF;
        END
        ");
    }

    public function down(): void
    {
        DB::unprepared('DROP TRIGGER IF EXISTS trg_campanias_sync_infestaciones_u');
        DB::unprepared('DROP TRIGGER IF EXISTS trg_infestaciones_bi');
        DB::unprepared('DROP TRIGGER IF EXISTS trg_infestaciones_bu');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * trg_campanias_sync_infestaciones_u reasignaba todas las infestaciones del campo en CUALQUIER update de
 * campos_campanias (área, kg de cosecha, métricas…). Ahora solo lo hace si cambian campo o fechas, igual que
 * trg_campos_campanias_au con los ingresos de cochinilla.
 */
return new class extends Migration {
    public function up(): void
    {
        DB::unprepared('DROP TRIGGER IF EXISTS trg_campanias_sync_infestaciones_u');
        DB::unprepared("
        CREATE TRIGGER trg_campanias_sync_infestaciones_u
        AFTER UPDATE ON campos_campanias
        FOR EACH ROW
        BEGIN
            IF NEW.campo <> OLD.campo
               OR NEW.fecha_inicio <> OLD.fecha_inicio
               OR IFNULL(NEW.fecha_fin, '9999-12-31') <> IFNULL(OLD.fecha_fin, '9999-12-31') THEN

                UPDATE cochinilla_infestaciones
                SET campo_campania_id = NULL
                WHERE campo_campania_id = OLD.id;

                UPDATE cochinilla_infestaciones i
                SET campo_campania_id = NEW.id
                WHERE i.campo_nombre = NEW.campo
                  AND i.fecha >= NEW.fecha_inicio
                  AND (NEW.fecha_fin IS NULL OR i.fecha <= NEW.fecha_fin);
            END IF;
        END
        ");
    }

    public function down(): void
    {
        DB::unprepared('DROP TRIGGER IF EXISTS trg_campanias_sync_infestaciones_u');
        DB::unprepared("
        CREATE TRIGGER trg_campanias_sync_infestaciones_u
        AFTER UPDATE ON campos_campanias
        FOR EACH ROW
        BEGIN
            UPDATE cochinilla_infestaciones
            SET campo_campania_id = NULL
            WHERE campo_nombre = NEW.campo
              AND campo_campania_id = OLD.id;

            UPDATE cochinilla_infestaciones i
            SET campo_campania_id = NEW.id
            WHERE i.campo_nombre = NEW.campo
              AND i.fecha >= NEW.fecha_inicio
              AND (NEW.fecha_fin IS NULL OR i.fecha <= NEW.fecha_fin);
        END
        ");
    }
};

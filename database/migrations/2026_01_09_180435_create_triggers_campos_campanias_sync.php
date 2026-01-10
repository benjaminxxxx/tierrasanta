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
        DB::unprepared('DROP TRIGGER IF EXISTS trg_campos_campanias_ai');
        DB::unprepared('DROP TRIGGER IF EXISTS trg_campos_campanias_au');

        /* AFTER INSERT */
        DB::unprepared("
        CREATE TRIGGER trg_campos_campanias_ai
        AFTER INSERT ON campos_campanias
        FOR EACH ROW
        BEGIN
            UPDATE cochinilla_ingresos
            SET campo_campania_id = NEW.id
            WHERE campo = NEW.campo
              AND fecha >= NEW.fecha_inicio
              AND (NEW.fecha_fin IS NULL OR fecha <= NEW.fecha_fin);
        END
    ");

        /* AFTER UPDATE */
        DB::unprepared("
        CREATE TRIGGER trg_campos_campanias_au
        AFTER UPDATE ON campos_campanias
        FOR EACH ROW
        BEGIN
            IF NEW.campo <> OLD.campo
               OR NEW.fecha_inicio <> OLD.fecha_inicio
               OR IFNULL(NEW.fecha_fin, '9999-12-31') <> IFNULL(OLD.fecha_fin, '9999-12-31') THEN

                /* 1. Limpiar ingresos que ya no pertenecen */
                UPDATE cochinilla_ingresos
                SET campo_campania_id = NULL
                WHERE campo_campania_id = OLD.id
                  AND (
                        campo <> NEW.campo
                     OR fecha < NEW.fecha_inicio
                     OR (NEW.fecha_fin IS NOT NULL AND fecha > NEW.fecha_fin)
                  );

                /* 2. Asignar ingresos que sí pertenecen */
                UPDATE cochinilla_ingresos
                SET campo_campania_id = NEW.id
                WHERE campo = NEW.campo
                  AND fecha >= NEW.fecha_inicio
                  AND (NEW.fecha_fin IS NULL OR fecha <= NEW.fecha_fin);

            END IF;
        END
    ");
    }


    public function down(): void
    {
        DB::unprepared('DROP TRIGGER IF EXISTS trg_campos_campanias_ai');
        DB::unprepared('DROP TRIGGER IF EXISTS trg_campos_campanias_au');
    }
};

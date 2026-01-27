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
        // --- TRIGGERS PARA CUAD_COSTOS_DIARIOS_GRUPOS (Cuando cambia el precio) ---

        // 1. Al INSERTAR un costo: Si ya había registros diarios, que tomen este nuevo precio
        DB::unprepared("
        CREATE TRIGGER tr_costos_insert_sync
        AFTER INSERT ON cuad_costos_diarios_grupos
        FOR EACH ROW
        BEGIN
            UPDATE cuad_registros_diarios 
            SET jornal_aplicado = NEW.jornal
            WHERE codigo_grupo = NEW.codigo_grupo AND fecha = NEW.fecha;
        END
    ");

        DB::unprepared("
    CREATE TRIGGER tr_costos_update_sync
    AFTER UPDATE ON cuad_costos_diarios_grupos
    FOR EACH ROW
    BEGIN
        -- 1. Si cambió la fecha o el grupo, 'limpiamos' los registros antiguos
        IF (OLD.fecha <> NEW.fecha OR OLD.codigo_grupo <> NEW.codigo_grupo) THEN
            UPDATE cuad_registros_diarios 
            SET jornal_aplicado = 0
            WHERE codigo_grupo = OLD.codigo_grupo AND fecha = OLD.fecha;
        END IF;

        -- 2. Actualizamos los registros de la nueva ubicación (o el nuevo precio)
        UPDATE cuad_registros_diarios 
        SET jornal_aplicado = NEW.jornal
        WHERE codigo_grupo = NEW.codigo_grupo AND fecha = NEW.fecha;
    END
");

        // --- TRIGGERS PARA CUAD_REGISTROS_DIARIOS (Cuando creas o mueves un registro) ---

        // 3. Al INSERTAR un registro diario: Busca automáticamente el precio actual
        DB::unprepared("
        CREATE TRIGGER tr_registros_insert_fetch
        BEFORE INSERT ON cuad_registros_diarios
        FOR EACH ROW
        BEGIN
            SET NEW.jornal_aplicado = (
                SELECT COALESCE(jornal, 0) 
                FROM cuad_costos_diarios_grupos 
                WHERE codigo_grupo = NEW.codigo_grupo AND fecha = NEW.fecha 
                LIMIT 1
            );
        END
    ");

        // 4. Al ACTUALIZAR un registro diario: Si cambian grupo o fecha, busca el nuevo precio
        DB::unprepared("
        CREATE TRIGGER tr_registros_update_fetch
        BEFORE UPDATE ON cuad_registros_diarios
        FOR EACH ROW
        BEGIN
            IF (OLD.codigo_grupo <> NEW.codigo_grupo OR OLD.fecha <> NEW.fecha) THEN
                SET NEW.jornal_aplicado = (
                    SELECT COALESCE(jornal, 0) 
                    FROM cuad_costos_diarios_grupos 
                    WHERE codigo_grupo = NEW.codigo_grupo AND fecha = NEW.fecha 
                    LIMIT 1
                );
            END IF;
        END
    ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::unprepared("DROP TRIGGER IF EXISTS tr_costos_insert_sync");
        DB::unprepared("DROP TRIGGER IF EXISTS tr_costos_update_sync");
        DB::unprepared("DROP TRIGGER IF EXISTS tr_registros_insert_fetch");
        DB::unprepared("DROP TRIGGER IF EXISTS tr_registros_update_fetch");
    }
};

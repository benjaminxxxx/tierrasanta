<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Cuadrilla: lo pagado no se borra ni se cambia por arrastre.
 *
 * 1) Triggers del jornal del día (cuad_costos_diarios_grupos → cuad_registros_diarios.jornal_aplicado):
 *    - Cambiar el costo de un día ya no cambia el jornal de los registros ya pagados (esta_pagado o con desglose):
 *      antes, editar la semana después de pagarla cambiaba lo pagado sin aviso.
 *    - Borrar el costo de un día (dejarlo vacío en la grilla) deja el jornal en 0: antes no había trigger y los
 *      registros se quedaban con el jornal anterior.
 *    - Un registro sin costo para su día queda con jornal 0 (antes quedaba NULL).
 * 2) Llaves foráneas: borrar un tramo, un grupo del tramo o un cuadrillero del tramo ya no borra en cascada sus
 *    registros diarios ni sus gastos adicionales (antes se perdían horas, bonos y pagos). La base rechaza el borrado
 *    y el sistema decide qué se puede borrar (TramoLaboralServicio).
 * No cambia ningún dato existente (solo pone 0 donde el jornal quedó NULL).
 */
return new class extends Migration {
    private const NO_PAGADO = 'esta_pagado = 0 AND desglose_detalle_id IS NULL';

    public function up(): void
    {
        foreach (['tr_costos_insert_sync', 'tr_costos_update_sync', 'tr_costos_delete_sync', 'tr_registros_insert_fetch', 'tr_registros_update_fetch'] as $t) {
            DB::unprepared("DROP TRIGGER IF EXISTS {$t}");
        }
        $noPagado = self::NO_PAGADO;

        DB::unprepared("
            CREATE TRIGGER tr_costos_insert_sync AFTER INSERT ON cuad_costos_diarios_grupos FOR EACH ROW
            BEGIN
                UPDATE cuad_registros_diarios SET jornal_aplicado = NEW.jornal
                WHERE codigo_grupo = NEW.codigo_grupo AND fecha = NEW.fecha AND {$noPagado};
            END
        ");
        DB::unprepared("
            CREATE TRIGGER tr_costos_update_sync AFTER UPDATE ON cuad_costos_diarios_grupos FOR EACH ROW
            BEGIN
                IF (OLD.fecha <> NEW.fecha OR OLD.codigo_grupo <> NEW.codigo_grupo) THEN
                    UPDATE cuad_registros_diarios SET jornal_aplicado = 0
                    WHERE codigo_grupo = OLD.codigo_grupo AND fecha = OLD.fecha AND {$noPagado};
                END IF;
                UPDATE cuad_registros_diarios SET jornal_aplicado = NEW.jornal
                WHERE codigo_grupo = NEW.codigo_grupo AND fecha = NEW.fecha AND {$noPagado};
            END
        ");
        DB::unprepared("
            CREATE TRIGGER tr_costos_delete_sync AFTER DELETE ON cuad_costos_diarios_grupos FOR EACH ROW
            BEGIN
                UPDATE cuad_registros_diarios SET jornal_aplicado = 0
                WHERE codigo_grupo = OLD.codigo_grupo AND fecha = OLD.fecha AND {$noPagado};
            END
        ");
        DB::unprepared("
            CREATE TRIGGER tr_registros_insert_fetch BEFORE INSERT ON cuad_registros_diarios FOR EACH ROW
            BEGIN
                SET NEW.jornal_aplicado = COALESCE((
                    SELECT jornal FROM cuad_costos_diarios_grupos
                    WHERE codigo_grupo = NEW.codigo_grupo AND fecha = NEW.fecha LIMIT 1
                ), 0);
            END
        ");
        DB::unprepared("
            CREATE TRIGGER tr_registros_update_fetch BEFORE UPDATE ON cuad_registros_diarios FOR EACH ROW
            BEGIN
                IF (OLD.codigo_grupo <> NEW.codigo_grupo OR OLD.fecha <> NEW.fecha) THEN
                    SET NEW.jornal_aplicado = COALESCE((
                        SELECT jornal FROM cuad_costos_diarios_grupos
                        WHERE codigo_grupo = NEW.codigo_grupo AND fecha = NEW.fecha LIMIT 1
                    ), 0);
                END IF;
            END
        ");

        DB::table('cuad_registros_diarios')->whereNull('jornal_aplicado')->update(['jornal_aplicado' => 0]);

        // Sin cascada hacia lo registrado: la base rechaza borrar un tramo o un cuadrillero del tramo con registros
        $this->cambiarFk('cuad_registros_diarios', 'tramo_cuadrillero_id', 'cuad_tramo_cuadrilleros', 'RESTRICT');
        $this->cambiarFk('cuad_registros_diarios', 'tramo_laboral_id', 'cuad_tramos_laborales', 'RESTRICT');
        $this->cambiarFk('cuad_gastos_grupos', 'cuad_tramo_laboral_id', 'cuad_tramos_laborales', 'RESTRICT');
    }

    public function down(): void
    {
        $this->cambiarFk('cuad_registros_diarios', 'tramo_cuadrillero_id', 'cuad_tramo_cuadrilleros', 'CASCADE');
        $this->cambiarFk('cuad_registros_diarios', 'tramo_laboral_id', 'cuad_tramos_laborales', 'CASCADE');
        $this->cambiarFk('cuad_gastos_grupos', 'cuad_tramo_laboral_id', 'cuad_tramos_laborales', 'CASCADE');

        foreach (['tr_costos_insert_sync', 'tr_costos_update_sync', 'tr_costos_delete_sync', 'tr_registros_insert_fetch', 'tr_registros_update_fetch'] as $t) {
            DB::unprepared("DROP TRIGGER IF EXISTS {$t}");
        }
        // Versión anterior (2026_01_27_170609_create_sync_costos_triggers)
        DB::unprepared("CREATE TRIGGER tr_costos_insert_sync AFTER INSERT ON cuad_costos_diarios_grupos FOR EACH ROW BEGIN
            UPDATE cuad_registros_diarios SET jornal_aplicado = NEW.jornal WHERE codigo_grupo = NEW.codigo_grupo AND fecha = NEW.fecha; END");
        DB::unprepared("CREATE TRIGGER tr_costos_update_sync AFTER UPDATE ON cuad_costos_diarios_grupos FOR EACH ROW BEGIN
            IF (OLD.fecha <> NEW.fecha OR OLD.codigo_grupo <> NEW.codigo_grupo) THEN
                UPDATE cuad_registros_diarios SET jornal_aplicado = 0 WHERE codigo_grupo = OLD.codigo_grupo AND fecha = OLD.fecha;
            END IF;
            UPDATE cuad_registros_diarios SET jornal_aplicado = NEW.jornal WHERE codigo_grupo = NEW.codigo_grupo AND fecha = NEW.fecha; END");
        DB::unprepared("CREATE TRIGGER tr_registros_insert_fetch BEFORE INSERT ON cuad_registros_diarios FOR EACH ROW BEGIN
            SET NEW.jornal_aplicado = (SELECT COALESCE(jornal, 0) FROM cuad_costos_diarios_grupos WHERE codigo_grupo = NEW.codigo_grupo AND fecha = NEW.fecha LIMIT 1); END");
        DB::unprepared("CREATE TRIGGER tr_registros_update_fetch BEFORE UPDATE ON cuad_registros_diarios FOR EACH ROW BEGIN
            IF (OLD.codigo_grupo <> NEW.codigo_grupo OR OLD.fecha <> NEW.fecha) THEN
                SET NEW.jornal_aplicado = (SELECT COALESCE(jornal, 0) FROM cuad_costos_diarios_grupos WHERE codigo_grupo = NEW.codigo_grupo AND fecha = NEW.fecha LIMIT 1);
            END IF; END");
    }

    /** Cambia la regla ON DELETE de la llave foránea de una columna. */
    private function cambiarFk(string $tabla, string $columna, string $referencia, string $regla): void
    {
        $nombre = DB::selectOne('SELECT CONSTRAINT_NAME n FROM information_schema.KEY_COLUMN_USAGE
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ? AND REFERENCED_TABLE_NAME = ?', [$tabla, $columna, $referencia])?->n;
        if ($nombre) {
            DB::statement("ALTER TABLE {$tabla} DROP FOREIGN KEY {$nombre}");
        }
        DB::statement("ALTER TABLE {$tabla} ADD CONSTRAINT {$tabla}_{$columna}_foreign FOREIGN KEY ({$columna}) REFERENCES {$referencia} (id) ON DELETE {$regla}");
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cuad_costos_diarios_grupos', function (Blueprint $table) {
            $table->id();
            $table->string('codigo_grupo');
            $table->date('fecha');
            $table->decimal('jornal', 8, 2)->nullable();

            $table->unique(['codigo_grupo', 'fecha']);
        });

        // Triggers (las tablas que usan se resuelven al ejecutarse, no al crearse)
        DB::unprepared(<<<'SQL'
CREATE TRIGGER `tr_costos_insert_sync` AFTER INSERT ON `cuad_costos_diarios_grupos` FOR EACH ROW
BEGIN
                UPDATE cuad_registros_diarios SET jornal_aplicado = NEW.jornal
                WHERE codigo_grupo = NEW.codigo_grupo AND fecha = NEW.fecha AND esta_pagado = 0 AND desglose_detalle_id IS NULL;
            END
SQL);
        DB::unprepared(<<<'SQL'
CREATE TRIGGER `tr_costos_update_sync` AFTER UPDATE ON `cuad_costos_diarios_grupos` FOR EACH ROW
BEGIN
                IF (OLD.fecha <> NEW.fecha OR OLD.codigo_grupo <> NEW.codigo_grupo) THEN
                    UPDATE cuad_registros_diarios SET jornal_aplicado = 0
                    WHERE codigo_grupo = OLD.codigo_grupo AND fecha = OLD.fecha AND esta_pagado = 0 AND desglose_detalle_id IS NULL;
                END IF;
                UPDATE cuad_registros_diarios SET jornal_aplicado = NEW.jornal
                WHERE codigo_grupo = NEW.codigo_grupo AND fecha = NEW.fecha AND esta_pagado = 0 AND desglose_detalle_id IS NULL;
            END
SQL);
        DB::unprepared(<<<'SQL'
CREATE TRIGGER `tr_costos_delete_sync` AFTER DELETE ON `cuad_costos_diarios_grupos` FOR EACH ROW
BEGIN
                UPDATE cuad_registros_diarios SET jornal_aplicado = 0
                WHERE codigo_grupo = OLD.codigo_grupo AND fecha = OLD.fecha AND esta_pagado = 0 AND desglose_detalle_id IS NULL;
            END
SQL);
    }

    public function down(): void
    {
        DB::unprepared('DROP TRIGGER IF EXISTS `tr_costos_insert_sync`');
        DB::unprepared('DROP TRIGGER IF EXISTS `tr_costos_update_sync`');
        DB::unprepared('DROP TRIGGER IF EXISTS `tr_costos_delete_sync`');
        Schema::dropIfExists('cuad_costos_diarios_grupos');
    }
};

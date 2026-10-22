<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cochinilla_ingresos', function (Blueprint $table) {
            $table->id();
            $table->integer('lote');
            $table->date('fecha');
            $table->string('campo');
            $table->decimal('area', 8, 3)->nullable();
            $table->foreignId('campo_campania_id')->nullable()->constrained('campos_campanias')->nullOnDelete();
            $table->string('campo_origen', 100)->nullable()->comment('Campo de donde es la cochinilla');
            $table->foreignId('campania_origen_id')->nullable()->constrained('campos_campanias')->nullOnDelete();
            $table->string('origen_estado', 20)->nullable()->comment('propio | infestacion | varios | sin_infestacion | manual');
            $table->string('origen_detalle', 300)->nullable();
            $table->string('observacion')->nullable();
            $table->decimal('proveedor_kg_exportado', 8, 2)->nullable();
            $table->decimal('kg_ha', 8, 2)->nullable();
            $table->decimal('total_kilos', 8, 2)->nullable();
            $table->decimal('diferencia_kilos', 8, 2)->default(0.00);
            $table->decimal('porcentaje_diferencia', 5, 2)->default(0.00);
            $table->decimal('venteado_kilos_ingresados', 8, 2)->default(0.00);
            $table->decimal('venteado_limpia', 8, 2)->default(0.00);
            $table->decimal('venteado_basura', 8, 2)->default(0.00);
            $table->decimal('venteado_polvillo', 8, 2)->default(0.00);
            $table->decimal('venteado_limpia_porcentaje', 5, 2)->default(0.00);
            $table->decimal('venteado_basura_porcentaje', 5, 2)->default(0.00);
            $table->decimal('venteado_polvillo_porcentaje', 5, 2)->default(0.00);
            $table->decimal('venteado_diferencia_kilos', 8, 2)->default(0.00);
            $table->decimal('venteado_diferencia_porcentaje', 5, 2)->default(0.00);
            $table->decimal('filtrado_kilos_ingresados', 8, 2)->default(0.00);
            $table->decimal('filtrado_primera', 8, 2)->default(0.00);
            $table->decimal('filtrado_segunda', 8, 2)->default(0.00);
            $table->decimal('filtrado_tercera', 8, 2)->default(0.00);
            $table->decimal('filtrado_piedra', 8, 2)->default(0.00);
            $table->decimal('filtrado_basura', 8, 2)->default(0.00);
            $table->decimal('filtrado_primera_porcentaje', 5, 2)->default(0.00);
            $table->decimal('filtrado_segunda_porcentaje', 5, 2)->default(0.00);
            $table->decimal('filtrado_tercera_porcentaje', 5, 2)->default(0.00);
            $table->decimal('filtrado_piedra_porcentaje', 5, 2)->default(0.00);
            $table->decimal('filtrado_basura_porcentaje', 5, 2)->default(0.00);
            $table->decimal('filtrado_diferencia_kilos', 8, 2)->default(0.00);
            $table->decimal('filtrado_diferencia_porcentaje', 5, 2)->default(0.00);
            $table->decimal('stock_disponible', 10, 2)->nullable();
            $table->foreignId('creado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('editado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique('lote');

            $table->foreign('observacion')->references('codigo')->on('cochinilla_observaciones')->nullOnDelete();
        });

        // Triggers (las tablas que usan se resuelven al ejecutarse, no al crearse)
        DB::unprepared(<<<'SQL'
CREATE TRIGGER `trg_cochinilla_ingresos_bi` BEFORE INSERT ON `cochinilla_ingresos` FOR EACH ROW
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
SQL);
        DB::unprepared(<<<'SQL'
CREATE TRIGGER `trg_cochinilla_ingresos_bu` BEFORE UPDATE ON `cochinilla_ingresos` FOR EACH ROW
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
SQL);
        DB::unprepared(<<<'SQL'
CREATE TRIGGER `trg_cochinilla_ingresos_filt_i` AFTER INSERT ON `cochinilla_ingresos` FOR EACH ROW
BEGIN
            UPDATE cochinilla_filtrados
            SET cochinilla_ingreso_id = NEW.id
            WHERE lote = NEW.lote
              AND cochinilla_ingreso_id IS NULL;
        END
SQL);
        DB::unprepared(<<<'SQL'
CREATE TRIGGER `trg_cochinilla_ingresos_vent_i` AFTER INSERT ON `cochinilla_ingresos` FOR EACH ROW
BEGIN
            UPDATE cochinilla_venteados
            SET cochinilla_ingreso_id = NEW.id
            WHERE lote = NEW.lote
              AND cochinilla_ingreso_id IS NULL;
        END
SQL);
        DB::unprepared(<<<'SQL'
CREATE TRIGGER `trg_cochinilla_ingresos_filt_u` AFTER UPDATE ON `cochinilla_ingresos` FOR EACH ROW
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
SQL);
        DB::unprepared(<<<'SQL'
CREATE TRIGGER `trg_cochinilla_ingresos_vent_u` AFTER UPDATE ON `cochinilla_ingresos` FOR EACH ROW
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
SQL);
    }

    public function down(): void
    {
        DB::unprepared('DROP TRIGGER IF EXISTS `trg_cochinilla_ingresos_bi`');
        DB::unprepared('DROP TRIGGER IF EXISTS `trg_cochinilla_ingresos_bu`');
        DB::unprepared('DROP TRIGGER IF EXISTS `trg_cochinilla_ingresos_filt_i`');
        DB::unprepared('DROP TRIGGER IF EXISTS `trg_cochinilla_ingresos_vent_i`');
        DB::unprepared('DROP TRIGGER IF EXISTS `trg_cochinilla_ingresos_filt_u`');
        DB::unprepared('DROP TRIGGER IF EXISTS `trg_cochinilla_ingresos_vent_u`');
        Schema::dropIfExists('cochinilla_ingresos');
    }
};

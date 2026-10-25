<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('campos_campanias', function (Blueprint $table) {
            $table->id();
            $table->string('nombre_campania')->nullable();
            $table->string('campo');
            $table->decimal('gasto_fdm', 8, 2)->nullable();
            $table->decimal('gasto_agua', 8, 2)->nullable();
            $table->decimal('gasto_planilla', 8, 2)->nullable();
            $table->decimal('gasto_cuadrilla', 8, 2)->nullable();
            $table->date('fecha_inicio');
            $table->date('fecha_fin')->nullable();
            $table->foreignId('usuario_modificador')->nullable()->constrained('users')->nullOnDelete();
            $table->date('infestacion_fecha')->nullable();
            $table->string('infestacion_duracion_desde_campania')->nullable();
            $table->integer('infestacion_numero_pencas')->nullable();
            $table->decimal('infestacion_kg_totales_madre', 8, 2)->nullable();
            $table->decimal('infestacion_kg_madre_infestador_carton', 8, 2)->nullable();
            $table->decimal('infestacion_kg_madre_infestador_tubos', 8, 2)->nullable();
            $table->decimal('infestacion_kg_madre_infestador_mallita', 8, 2)->nullable();
            $table->text('infestacion_procedencia_madres')->nullable();
            $table->double('infestacion_cantidad_madres_por_infestador_carton')->nullable();
            $table->double('infestacion_cantidad_madres_por_infestador_tubos')->nullable();
            $table->double('infestacion_cantidad_madres_por_infestador_mallita')->nullable();
            $table->integer('infestacion_cantidad_infestadores_carton')->nullable();
            $table->integer('infestacion_cantidad_infestadores_tubos')->nullable();
            $table->integer('infestacion_cantidad_infestadores_mallita')->nullable();
            $table->date('infestacion_fecha_recojo_vaciado_infestadores')->nullable();
            $table->integer('infestacion_permanencia_infestadores')->nullable();
            $table->date('infestacion_fecha_colocacion_malla')->nullable();
            $table->date('infestacion_fecha_retiro_malla')->nullable();
            $table->integer('infestacion_permanencia_malla')->nullable();
            $table->text('gasto_planilla_file')->nullable();
            $table->text('gasto_cuadrilla_file')->nullable();
            $table->text('gasto_resumen_bdd_file')->nullable();
            $table->date('riego_inicio')->nullable();
            $table->date('riego_fin')->nullable();
            $table->decimal('riego_descarga_ha_hora', 6, 2)->nullable();
            $table->decimal('riego_hrs_ini_infest', 6, 2)->nullable();
            $table->decimal('riego_m3_ini_infest', 8, 2)->nullable();
            $table->decimal('riego_hrs_infest_reinf', 6, 2)->nullable();
            $table->decimal('riego_m3_infest_reinf', 8, 2)->nullable();
            $table->decimal('riego_hrs_reinf_cosecha', 6, 2)->nullable();
            $table->decimal('riego_m3_reinf_cosecha', 8, 2)->nullable();
            $table->decimal('riego_hrs_acumuladas', 6, 2)->nullable();
            $table->decimal('riego_m3_acum_ha', 8, 2)->nullable();
            $table->decimal('acid_prom', 5, 2)->nullable();
            $table->decimal('acid_infest', 5, 2)->nullable();
            $table->decimal('acid_secado', 5, 2)->nullable();
            $table->decimal('acid_poda_infest', 5, 2)->nullable();
            $table->decimal('acid_poda_losa', 5, 2)->nullable();
            $table->decimal('acid_tam', 5, 2)->nullable();
            $table->decimal('area', 10, 4)->nullable();
            $table->date('cosch_fecha')->nullable();
            $table->string('cosch_tiempo_inf_cosch')->nullable();
            $table->string('cosch_tiempo_reinf_cosch')->nullable();
            $table->string('cosch_tiempo_ini_cosch')->nullable();
            $table->decimal('cosch_kg_fresca_carton', 8, 2)->nullable();
            $table->decimal('cosch_kg_fresca_tubo', 8, 2)->nullable();
            $table->decimal('cosch_kg_fresca_malla', 8, 2)->nullable();
            $table->decimal('cosch_kg_fresca_losa', 8, 2)->nullable();
            $table->string('cosch_destino_carton')->nullable();
            $table->string('cosch_destino_tubo')->nullable();
            $table->string('cosch_destino_malla')->nullable();
            $table->decimal('cosch_kg_seca_carton', 8, 2)->nullable();
            $table->decimal('cosch_kg_seca_tubo', 8, 2)->nullable();
            $table->decimal('cosch_kg_seca_malla', 8, 2)->nullable();
            $table->decimal('cosch_kg_seca_losa', 8, 2)->nullable();
            $table->decimal('cosch_kg_seca_venta_madre', 8, 2)->nullable();
            $table->decimal('cosch_factor_fs_carton', 5, 2)->nullable();
            $table->decimal('cosch_factor_fs_tubo', 5, 2)->nullable();
            $table->decimal('cosch_factor_fs_malla', 5, 2)->nullable();
            $table->decimal('cosch_factor_fs_losa', 5, 2)->nullable();
            $table->decimal('cosch_total_cosecha', 10, 2)->nullable();
            $table->decimal('cosch_total_campania', 10, 2)->nullable();
            $table->integer('eval_cosch_conteo_individuos')->nullable();
            $table->integer('eval_cosch_proj_1')->nullable();
            $table->integer('eval_cosch_proj_2')->nullable();
            $table->integer('eval_cosch_proj_coch_x_gramo')->nullable();
            $table->integer('eval_cosch_proj_gramos_x_penca')->nullable();
            $table->integer('eval_cosch_proj_penca_inf')->nullable();
            $table->integer('eval_cosch_proj_rdto_ha')->nullable();
            $table->integer('proj_rdto_poda_muestra')->nullable();
            $table->integer('proj_rdto_metros_cama_ha')->nullable();
            $table->integer('proj_rdto_prom_rdto_ha')->nullable();
            $table->decimal('proj_rdto_rel_fs', 8, 2)->nullable();
            $table->date('cosechamadres_fecha_cosecha')->nullable();
            $table->string('cosechamadres_tiempo_infestacion_a_cosecha')->nullable();
            $table->decimal('cosechamadres_destino_madres_fresco', 8, 2)->nullable();
            $table->decimal('cosechamadres_infestador_carton_campos', 8, 2)->nullable();
            $table->decimal('cosechamadres_infestador_tubo_campos', 8, 2)->nullable();
            $table->decimal('cosechamadres_infestador_mallita_campos', 8, 2)->nullable();
            $table->decimal('cosechamadres_para_secado', 8, 2)->nullable();
            $table->decimal('cosechamadres_para_venta_fresco', 8, 2)->nullable();
            $table->decimal('cosechamadres_recuperacion_madres_seco_carton', 8, 2)->nullable();
            $table->decimal('cosechamadres_recuperacion_madres_seco_tubo', 8, 2)->nullable();
            $table->decimal('cosechamadres_recuperacion_madres_seco_mallita', 8, 2)->nullable();
            $table->decimal('cosechamadres_recuperacion_madres_seco_secado', 8, 2)->nullable();
            $table->decimal('cosechamadres_recuperacion_madres_seco_fresco', 8, 2)->nullable();
            $table->decimal('cosechamadres_conversion_fresco_seco_carton', 8, 2)->nullable();
            $table->decimal('cosechamadres_conversion_fresco_seco_tubo', 8, 2)->nullable();
            $table->decimal('cosechamadres_conversion_fresco_seco_mallita', 8, 2)->nullable();
            $table->decimal('cosechamadres_conversion_fresco_seco_secado', 8, 2)->nullable();
            $table->decimal('cosechamadres_conversion_fresco_seco_fresco', 8, 2)->nullable();
            $table->date('reinfestacion_fecha')->nullable();
            $table->string('reinfestacion_duracion_desde_infestacion')->nullable();
            $table->integer('reinfestacion_numero_pencas')->nullable();
            $table->decimal('reinfestacion_kg_totales_madre', 8, 2)->nullable();
            $table->decimal('reinfestacion_kg_madre_infestador_carton', 8, 2)->nullable();
            $table->decimal('reinfestacion_kg_madre_infestador_tubos', 8, 2)->nullable();
            $table->decimal('reinfestacion_kg_madre_infestador_mallita', 8, 2)->nullable();
            $table->text('reinfestacion_procedencia_madres')->nullable();
            $table->double('reinfestacion_cantidad_madres_por_infestador_carton')->nullable();
            $table->double('reinfestacion_cantidad_madres_por_infestador_tubos')->nullable();
            $table->double('reinfestacion_cantidad_madres_por_infestador_mallita')->nullable();
            $table->integer('reinfestacion_cantidad_infestadores_carton')->nullable();
            $table->integer('reinfestacion_cantidad_infestadores_tubos')->nullable();
            $table->integer('reinfestacion_cantidad_infestadores_mallita')->nullable();
            $table->date('reinfestacion_fecha_recojo_vaciado_infestadores')->nullable();
            $table->integer('reinfestacion_permanencia_infestadores')->nullable();
            $table->date('reinfestacion_fecha_colocacion_malla')->nullable();
            $table->date('reinfestacion_fecha_retiro_malla')->nullable();
            $table->integer('reinfestacion_permanencia_malla')->nullable();
            $table->string('variedad_tuna', 50)->nullable();
            $table->string('sistema_cultivo')->nullable();
            $table->decimal('tipo_cambio', 12, 4)->nullable();
            $table->decimal('costo_fertilizantes', 14, 4)->nullable();
            $table->decimal('costo_pesticidas', 14, 4)->nullable();
            $table->decimal('costo_combustibles', 14, 4)->nullable();
            $table->text('costo_fertilizantes_file')->nullable();
            $table->text('costo_pesticidas_file')->nullable();
            $table->text('costo_combustibles_file')->nullable();
            $table->integer('pencas_x_hectarea')->nullable();
            $table->date('pp_dia_cero_fecha_evaluacion')->nullable();
            $table->integer('pp_dia_cero_numero_pencas_madre')->nullable();
            $table->date('pp_resiembra_fecha_evaluacion')->nullable();
            $table->integer('pp_resiembra_numero_pencas_madre')->nullable();
            $table->date('brotexpiso_fecha_evaluacion')->nullable();
            $table->integer('brotexpiso_actual_brotes_2piso')->nullable();
            $table->integer('brotexpiso_brotes_2piso_n_dias')->nullable();
            $table->integer('brotexpiso_actual_brotes_3piso')->nullable();
            $table->integer('brotexpiso_brotes_3piso_n_dias')->nullable();
            $table->integer('brotexpiso_actual_total_brotes_2y3piso')->nullable();
            $table->integer('brotexpiso_total_brotes_2y3piso_n_dias')->nullable();
            $table->timestamps();
            $table->date('eval_infest_fecha_primera')->nullable();
            $table->date('eval_infest_fecha_segunda')->nullable();
            $table->date('eval_infest_fecha_tercera')->nullable();

            $table->unique(['campo', 'nombre_campania']);

            $table->foreign('campo')->references('nombre')->on('campos')->cascadeOnDelete();
        });

        // Triggers (las tablas que usan se resuelven al ejecutarse, no al crearse)
        DB::unprepared(<<<'SQL'
CREATE TRIGGER `trg_campos_campanias_ai` AFTER INSERT ON `campos_campanias` FOR EACH ROW
BEGIN
            UPDATE cochinilla_ingresos
            SET campo_campania_id = NEW.id
            WHERE campo = NEW.campo
              AND fecha >= NEW.fecha_inicio
              AND (NEW.fecha_fin IS NULL OR fecha <= NEW.fecha_fin);
        END
SQL);
        DB::unprepared(<<<'SQL'
CREATE TRIGGER `trg_campos_campanias_au` AFTER UPDATE ON `campos_campanias` FOR EACH ROW
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
SQL);
        DB::unprepared(<<<'SQL'
CREATE TRIGGER `trg_campanias_sync_infestaciones_u` AFTER UPDATE ON `campos_campanias` FOR EACH ROW
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
SQL);
    }

    public function down(): void
    {
        DB::unprepared('DROP TRIGGER IF EXISTS `trg_campos_campanias_ai`');
        DB::unprepared('DROP TRIGGER IF EXISTS `trg_campos_campanias_au`');
        DB::unprepared('DROP TRIGGER IF EXISTS `trg_campanias_sync_infestaciones_u`');
        Schema::dropIfExists('campos_campanias');
    }
};

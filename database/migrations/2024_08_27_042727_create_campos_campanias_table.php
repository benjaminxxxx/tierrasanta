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
        Schema::create('campos_campanias', function (Blueprint $table) {
            $table->id(); //modificado
            $table->string('nombre_campania')->nullable(); // Campo para la campaña, e.g., T.2024 o N2.2024
            $table->string('campo');
            $table->decimal('gasto_fdm', 8, 2)->nullable();
            $table->decimal('gasto_agua', 8, 2)->nullable();
            $table->decimal('gasto_planilla', 8, 2)->nullable();
            $table->decimal('gasto_cuadrilla', 8, 2)->nullable();
            $table->date('fecha_inicio'); // Campo para la fecha de vigencia
            $table->date('fecha_fin')->nullable();
            $table->unsignedBigInteger('usuario_modificador')->nullable();

            $table->date('infestacion_fecha')->nullable();
            $table->string('infestacion_duracion_desde_campania', 255)->nullable();
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

            // Grupo: Riego
            $table->date('riego_inicio')->nullable(); // Fecha de inicio de riego
            $table->date('riego_fin')->nullable();    // Fecha de fin de riego

            $table->decimal('riego_descarga_ha_hora', 6, 2)->nullable(); // m3/há/hora

            $table->decimal('riego_hrs_ini_infest', 6, 2)->nullable();  // Horas de riego de inicio a infestación
            $table->decimal('riego_m3_ini_infest', 8, 2)->nullable();   // Metros cúbicos de inicio a infestación

            $table->decimal('riego_hrs_infest_reinf', 6, 2)->nullable(); // Horas de riego de infestación a reinfestación
            $table->decimal('riego_m3_infest_reinf', 8, 2)->nullable();  // Metros cúbicos de infestación a reinfestación

            $table->decimal('riego_hrs_reinf_cosecha', 6, 2)->nullable(); // Horas de riego de reinf a cosecha
            $table->decimal('riego_m3_reinf_cosecha', 8, 2)->nullable();  // Metros cúbicos de reinf a cosecha

            $table->decimal('riego_hrs_acumuladas', 6, 2)->nullable(); // Total de horas acumuladas de riego
            $table->decimal('riego_m3_acum_ha', 8, 2)->nullable();      // m3 acumulado por hectárea

            $table->decimal('acid_prom', 5, 2)->nullable();
            $table->decimal('acid_infest', 5, 2)->nullable();
            $table->decimal('acid_secado', 5, 2)->nullable();
            $table->decimal('acid_poda_infest', 5, 2)->nullable();
            $table->decimal('acid_poda_losa', 5, 2)->nullable();
            $table->decimal('acid_tam', 5, 2)->nullable();

            $table->decimal('area')->nullable();
            $table->date('cosch_fecha')->nullable(); // Fecha de cosecha o poda

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
            $table->decimal('proj_rdto_rel_fs')->nullable();

            $table->date('cosechamadres_fecha_cosecha')->nullable();
            $table->string('cosechamadres_tiempo_infestacion_a_cosecha', 255)->nullable();
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
            $table->string('reinfestacion_duracion_desde_infestacion', 255)->nullable();
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
            $table->string('sistema_cultivo', 255)->nullable();
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
            $table->unique(['campo', 'nombre_campania']);

            $table->foreign('campo')->references('nombre')->on('campos')->onDelete('cascade');
            $table->foreign('usuario_modificador')->references('id')->on('users')->onDelete('set null');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('campos_campanias');
    }
};

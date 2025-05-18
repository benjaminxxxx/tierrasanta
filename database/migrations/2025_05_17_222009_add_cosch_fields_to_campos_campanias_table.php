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
        Schema::table('campos_campanias', function (Blueprint $table) {
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
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('campos_campanias', function (Blueprint $table) {
            $table->dropColumn([
                'area',
                'cosch_fecha',
                'cosch_tiempo_inf_cosch',
                'cosch_tiempo_reinf_cosch',
                'cosch_tiempo_ini_cosch',
                'cosch_destino_carton',
                'cosch_destino_tubo',
                'cosch_destino_malla',
                'cosch_kg_fresca_carton',
                'cosch_kg_fresca_tubo',
                'cosch_kg_fresca_malla',
                'cosch_kg_fresca_losa',
                'cosch_kg_seca_carton',
                'cosch_kg_seca_tubo',
                'cosch_kg_seca_malla',
                'cosch_kg_seca_losa',
                'cosch_kg_seca_venta_madre',
                'cosch_factor_fs_carton',
                'cosch_factor_fs_tubo',
                'cosch_factor_fs_malla',
                'cosch_factor_fs_losa',
                'cosch_total_cosecha',
                'cosch_total_campania',
            ]);
        });
    }
};

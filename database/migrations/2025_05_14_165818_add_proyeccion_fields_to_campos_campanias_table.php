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
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('campos_campanias', function (Blueprint $table) {
            $table->dropColumn([
                'eval_cosch_conteo_individuos',
                'eval_cosch_proj_1',
                'eval_cosch_proj_2',
                'eval_cosch_proj_coch_x_gramo',
                'eval_cosch_proj_gramos_x_penca',
                'eval_cosch_proj_penca_inf',
                'eval_cosch_proj_rdto_ha',

                'proj_rdto_poda_muestra',
                'proj_rdto_metros_cama_ha',
                'proj_rdto_prom_rdto_ha',
                'proj_rdto_rel_fs',
            ]);
        });
    }
};

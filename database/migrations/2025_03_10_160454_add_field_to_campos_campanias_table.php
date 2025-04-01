<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('campos_campanias', function (Blueprint $table) {
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
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('campos_campanias', function (Blueprint $table) {
            $table->dropColumn([
                'variedad_tuna',
                'sistema_cultivo',
                'tipo_cambio',
                'costo_fertilizantes',
                'costo_pesticidas',
                'costo_combustibles',
                'costo_fertilizantes_file',
                'costo_pesticidas_file',
                'costo_combustibles_file',
                'pencas_x_hectarea',
                //pp = Poblacion de Plantas
                'pp_dia_cero_fecha_evaluacion',
                'pp_dia_cero_numero_pencas_madre',
                'pp_resiembra_fecha_evaluacion',
                'pp_resiembra_numero_pencas_madre',
            ]);
        });
    }
};

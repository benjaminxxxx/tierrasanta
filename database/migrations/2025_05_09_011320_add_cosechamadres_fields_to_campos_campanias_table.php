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
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('campos_campanias', function (Blueprint $table) {
            $table->dropColumn([
                'cosechamadres_fecha_cosecha',
                'cosechamadres_tiempo_infestacion_a_cosecha',
                'cosechamadres_destino_madres_fresco',
                'cosechamadres_infestador_carton_campos',
                'cosechamadres_infestador_tubo_campos',
                'cosechamadres_infestador_mallita_campos',
                'cosechamadres_para_secado',
                'cosechamadres_para_venta_fresco',
                'cosechamadres_recuperacion_madres_seco_carton',
                'cosechamadres_recuperacion_madres_seco_tubo',
                'cosechamadres_recuperacion_madres_seco_mallita',
                'cosechamadres_recuperacion_madres_seco_secado',
                'cosechamadres_recuperacion_madres_seco_fresco',
                'cosechamadres_conversion_fresco_seco_carton',
                'cosechamadres_conversion_fresco_seco_tubo',
                'cosechamadres_conversion_fresco_seco_mallita',
                'cosechamadres_conversion_fresco_seco_secado',
                'cosechamadres_conversion_fresco_seco_fresco',
            ]);
        });
    }
};

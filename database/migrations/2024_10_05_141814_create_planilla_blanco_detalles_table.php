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
        Schema::create('planilla_blanco_detalles', function (Blueprint $table) {
            $table->id();
            $table->string('documento', 8); 
            $table->string('nombres');
            $table->string('spp_snp');
            $table->string('empleado_grupo_color')->nullable();
            
            $table->integer('orden');
            $table->decimal('remuneracion_basica', 10, 2);
            $table->decimal('bonificacion', 10, 2)->nullable();
            $table->decimal('asignacion_familiar', 10, 2)->nullable();
            $table->decimal('compensacion_vacacional', 10, 2)->nullable();
            $table->decimal('sueldo_bruto', 10, 2)->nullable();
            $table->decimal('dscto_afp_seguro', 10, 2)->nullable();
            $table->string('dscto_afp_seguro_explicacion')->nullable();
            
            $table->decimal('cts', 10, 2)->nullable();
            $table->decimal('gratificaciones', 10, 2)->nullable();
            $table->decimal('essalud_gratificaciones', 10, 2)->nullable();
            //$table->decimal('pension', 10, 2)->nullable();            
            $table->decimal('beta_30', 10, 2)->nullable();
            $table->decimal('essalud', 10, 2)->nullable();
            $table->decimal('vida_ley', 10, 2)->nullable();
            $table->decimal('pension_sctr', 10, 2)->nullable();            
            $table->decimal('essalud_eps', 10, 2)->nullable();
            $table->decimal('sueldo_neto', 10, 2)->nullable();
            $table->decimal('rem_basica_essalud', 10, 2)->nullable();
            $table->decimal('rem_basica_asg_fam_essalud_cts_grat_beta', 10, 2)->nullable();            
            $table->decimal('jornal_diario', 10, 2)->nullable();
            $table->decimal('costo_hora', 10, 2)->nullable();
            $table->decimal('negro_sueldo_por_dia_total', 10, 2)->nullable();
            $table->decimal('negro_sueldo_por_hora_total', 12, 5)->nullable();
            $table->decimal('negro_otros_bonos_acumulados', 10, 2)->nullable();
            $table->decimal('negro_sueldo_final_empleado', 10, 2)->nullable();
            $table->string('esta_jubilado', 10)->nullable();
            // Nuevos campos con el prefijo 'negro_'
            $table->decimal('negro_diferencia_bonificacion', 10, 2)->nullable();
            $table->decimal('negro_sueldo_neto_total', 10, 2)->nullable();
            $table->decimal('negro_sueldo_bruto', 10, 2)->nullable();
            $table->decimal('negro_sueldo_por_dia', 10, 2)->nullable();
            $table->decimal('negro_sueldo_por_hora', 12, 5)->nullable();
            $table->decimal('negro_diferencia_por_hora', 10, 2)->nullable();
            $table->decimal('negro_diferencia_real', 10, 2)->nullable();

            $table->unsignedBigInteger('planilla_blanco_id');

            $table->timestamps();
            $table->foreign('planilla_blanco_id')->references('id')->on('planillas_blanco')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('planilla_blanco_detalles');
    }
};

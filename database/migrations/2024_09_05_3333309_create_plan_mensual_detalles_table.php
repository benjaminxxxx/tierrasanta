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
        Schema::create('plan_mensual_detalles', function (Blueprint $table) {
            $table->id();
            $table->string('documento', 8);
            $table->string('nombres');
            $table->string('grupo', 50)->nullable();
            $table->string('spp_snp')->nullable();
            $table->string('empleado_grupo_color')->nullable();
            $table->foreignId('plan_empleado_id')->nullable()->constrained('plan_empleados')->nullOnDelete();

            $table->integer('orden');
            $table->decimal('remuneracion_basica', 10, 2)->nullable();
            $table->decimal('bonificacion', 10, 2)->nullable();
            $table->decimal('asignacion_familiar', 10, 2)->nullable();
            $table->decimal('compensacion_vacacional', 10, 2)->nullable();
            $table->decimal('sueldo_bruto', 10, 2)->nullable();
            $table->decimal('dscto_afp_seguro', 10, 2)->nullable();
            $table->string('dscto_afp_seguro_explicacion')->nullable();

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

            // --- INSUMOS PARA EL CÁLCULO ---
            $table->decimal('negro_bono_asistencia', 10, 2)->default(0); // Los 100 manuales
            $table->decimal('negro_bono_productividad', 10, 2)->default(0); // Post-desempeño

            // --- VARIABLES DE TIEMPO (Insumos) ---
            $table->integer('dias_trabajados')->default(0);
            $table->decimal('horas_trabajadas', 8, 2)->default(0);

            // --- EL BLANCO (Lo que ya pagó la empresa por banco/planilla) ---
            $table->decimal('blanco_neto_pagar', 10, 2)->default(0);
            $table->unsignedTinyInteger('faltas_injustificadas')->default(0);

            $table->unsignedBigInteger('plan_mensual_id');

            $table->timestamps();
            $table->foreign('plan_mensual_id')->references('id')->on('plan_mensuales')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('plan_mensual_detalles');
    }
};

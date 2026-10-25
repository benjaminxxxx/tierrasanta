<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Un empleado en un mes de planilla agraria: una sola fila con todo lo del mes (antes repartido entre
 * plan_mensual_detalles y plan_mensual_personals).
 *
 *  1. Lista: nace al crearse la lista del mes (PlanillaListaMensualProceso) y ancla los registros diarios.
 *  2-5. Cálculo: lo llena "Generar planilla" (PlanillaServicio::generarProyeccion); calculado_en dice cuándo. Si el
 *     empleado no se pudo calcular (sin pensión, sin contrato…) estas columnas quedan vacías y calculado_en en null.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plan_mensual_detalles', function (Blueprint $table) {
            $table->id();

            // 1. Lista del registro diario
            $table->foreignId('plan_mensual_id')->constrained('plan_mensuales')->cascadeOnDelete();
            $table->foreignId('plan_empleado_id')->nullable()->constrained('plan_empleados')->nullOnDelete();
            $table->integer('orden')->comment('Orden en el registro diario (Gestionar lista)');
            $table->string('documento', 8)->nullable();
            $table->string('nombres');

            // 2. Datos del mes (contrato, pensión y edad al calcular)
            $table->timestamp('calculado_en')->nullable()->comment('Última vez que "Generar planilla" calculó la fila; null = no calculada');
            $table->unsignedInteger('orden_planilla')->nullable()->comment('Orden en la planilla y el PLAME (orden configurado)');
            $table->string('grupo', 50)->nullable();
            $table->string('empleado_grupo_color')->nullable();
            $table->string('sistema_pension', 10)->nullable();
            $table->boolean('es_pensionista')->default(false);
            $table->unsignedTinyInteger('edad')->nullable();

            // Proyectado
            $table->decimal('remuneracion_basica', 10, 2)->nullable();
            $table->decimal('bonificacion', 10, 2)->nullable();
            $table->decimal('asignacion_familiar', 10, 2)->nullable();
            $table->decimal('compensacion_vacacional', 10, 2)->nullable();
            $table->decimal('proyectado_dscto_afp_prima_seguro', 10, 2)->nullable();
            $table->decimal('proyectado_sueldo_neto_total', 10, 2)->nullable()->comment('Sueldo acordado con el trabajador');

            // 3. Días de suspensión del mes por código SUNAT (perfecta sp_, imperfecta si_)
            foreach (['sp_01', 'sp_02', 'sp_03', 'sp_04', 'sp_05', 'sp_06', 'sp_07', 'sp_08',
                'si_20', 'si_21', 'si_22', 'si_23', 'si_24', 'si_25', 'si_26', 'si_27'] as $columna) {
                $table->unsignedTinyInteger($columna)->default(0);
            }

            // 4. PLAME: días, horas, ingresos, descuentos, aportes y neto (valor final, con ajustes)
            $table->unsignedTinyInteger('plame_dias_no_laborados')->default(0);
            $table->unsignedTinyInteger('plame_dias_laborados')->default(0);
            $table->decimal('plame_total_horas', 7, 2)->default(0.00)->comment('Horas registradas en el registro diario');
            $table->decimal('plame_horas_jornada', 7, 2)->nullable()->comment('Horas de jornada ordinaria del PLAME (sin los días de suspensión)');
            $table->decimal('plame_0117_comp_vacacional', 10, 2)->default(0.00);
            $table->decimal('plame_0118_rem_vacacional', 10, 2)->default(0.00);
            $table->decimal('plame_0121_rem_jornal_basico', 10, 2)->default(0.00);
            $table->decimal('plame_0201_asignacion_familiar', 10, 2)->default(0.00);
            $table->decimal('plame_remuneracion_bruta', 10, 2)->default(0.00);
            $table->decimal('plame_0312_bonif_ext_temp', 10, 2)->default(0.00);
            $table->decimal('plame_0314_beta_30', 10, 2)->default(0.00);
            $table->decimal('plame_0406_gratif_fiestas_navidad', 10, 2)->default(0.00);
            $table->decimal('plame_0904_cts', 10, 2)->default(0.00);
            $table->decimal('plame_descuento_0601_comision_afp_pct', 10, 2)->default(0.00);
            $table->decimal('plame_descuento_0605_renta_5ta_retenida', 10, 2)->default(0.00);
            $table->decimal('plame_descuento_0606_prima_seguro_afp', 10, 2)->default(0.00);
            $table->decimal('plame_descuento_0607_snp', 10, 2)->default(0.00);
            $table->decimal('plame_descuento_0608_spp_aporte_obligatorio', 10, 2)->default(0.00);
            $table->decimal('plame_neto_a_pagar', 10, 2)->default(0.00);
            $table->decimal('plame_aporte_empleador_0803_poliza', 10, 2)->default(0.00);
            $table->decimal('plame_aporte_empleador_0804_essalud', 10, 2)->default(0.00);
            $table->decimal('plame_aporte_empleador_0805_sctr', 10, 2)->default(0.00);
            $table->decimal('plame_aporte_empleador_0810_eps', 10, 2)->default(0.00);
            $table->json('plame_ajustes')->nullable()->comment('Ajustes manuales por concepto: monto y motivo (se conservan al regenerar)');
            $table->json('plame_calculados')->nullable()->comment('Lo que calculó el sistema por concepto, sin ajustes');

            // 5. Pagos reales fuera del cálculo (vacaciones y bono de asistencia)
            $table->decimal('bonificacion_asistencia', 8, 2)->nullable();
            $table->decimal('vacaciones_plame_personalizado', 8, 2)->nullable();
            $table->decimal('vacaciones_neto_pagadas', 10, 2)->nullable();
            $table->decimal('vacaciones_negro', 10, 2)->nullable();

            $table->timestamps();

            $table->unique(['plan_mensual_id', 'plan_empleado_id'], 'plan_mensual_detalles_mes_empleado_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plan_mensual_detalles');
    }
};

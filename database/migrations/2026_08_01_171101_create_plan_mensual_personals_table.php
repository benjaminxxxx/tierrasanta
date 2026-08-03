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
        Schema::create('plan_mensual_personals', function (Blueprint $table) {
            $table->id();
            $table->string('nombres');
            $table->integer('orden');
            $table->string('grupo', 50)->nullable();
            $table->foreignId('plan_empleado_id')->nullable()->constrained('plan_empleados')->nullOnDelete();
            $table->unsignedBigInteger('plan_mensual_id');
            $table->string('empleado_grupo_color')->nullable();
            $table->string('sistema_pension', 10);
            $table->boolean('es_pensionista')->default(false);
            $table->unsignedTinyInteger('edad')->nullable();
            //proyectado_ (remuneracion_basica, bonif, asignacion_familiar, compensacion) = sueldo bruto
            $table->decimal('remuneracion_basica', 10, 2)->nullable();
            $table->decimal('bonificacion', 10, 2)->nullable();
            $table->decimal('asignacion_familiar', 10, 2)->nullable();
            $table->decimal('compensacion_vacacional', 10, 2)->nullable();
            $table->decimal('proyectado_dscto_afp_prima_seguro', 10, 2)->nullable();
            //negro real
            $table->decimal('proyectado_sueldo_neto_total', 10, 2)->nullable();
            // PLAME: Suspensión perfecta e imperfecta SP_01 al SP_08
            $table->unsignedTinyInteger('sp_01')->default(0);
            $table->unsignedTinyInteger('sp_02')->default(0);
            $table->unsignedTinyInteger('sp_03')->default(0);
            $table->unsignedTinyInteger('sp_04')->default(0);
            $table->unsignedTinyInteger('sp_05')->default(0);
            $table->unsignedTinyInteger('sp_06')->default(0);
            $table->unsignedTinyInteger('sp_07')->default(0);
            $table->unsignedTinyInteger('sp_08')->default(0);

            // PLAME: Suspensión imperfecta SI_20 al SI_27
            $table->unsignedTinyInteger('si_20')->default(0);
            $table->unsignedTinyInteger('si_21')->default(0);
            $table->unsignedTinyInteger('si_22')->default(0);
            $table->unsignedTinyInteger('si_23')->default(0);
            $table->unsignedTinyInteger('si_24')->default(0);
            $table->unsignedTinyInteger('si_25')->default(0);
            $table->unsignedTinyInteger('si_26')->default(0);
            $table->unsignedTinyInteger('si_27')->default(0);

            $table->unsignedTinyInteger('plame_dias_no_laborados')->default(0);
            $table->unsignedTinyInteger('plame_dias_laborados')->default(0);
            $table->decimal('plame_total_horas', 7, 2)->default(0);

            $table->decimal('plame_0117_comp_vacacional', 10, 2)->default(0);
            $table->decimal('plame_0118_rem_vacacional', 10, 2)->default(0);
            $table->decimal('plame_0121_rem_jornal_basico', 10, 2)->default(0);
            $table->decimal('plame_0201_asignacion_familiar', 10, 2)->default(0);

            $table->decimal('plame_remuneracion_bruta', 10, 2)->default(0);

            $table->decimal('plame_0312_bonif_ext_temp', 10, 2)->default(0);
            $table->decimal('plame_0314_beta_30', 10, 2)->default(0);
            $table->decimal('plame_0406_gratif_fiestas_navidad', 10, 2)->default(0);
            $table->decimal('plame_0904_cts', 10, 2)->default(0);

            $table->decimal('plame_descuento_0601_comision_afp_pct', 10, 2)->default(0);
            $table->decimal('plame_descuento_0605_renta_5ta_retenida', 10, 2)->default(0);
            $table->decimal('plame_descuento_0606_prima_seguro_afp', 10, 2)->default(0);
            $table->decimal('plame_descuento_0607_snp', 10, 2)->default(0);
            $table->decimal('plame_descuento_0608_spp_aporte_obligatorio', 10, 2)->default(0);

            $table->decimal('plame_neto_a_pagar', 10, 2)->default(0);

            $table->decimal('plame_aporte_empleador_0803_poliza', 10, 2)->default(0);
            $table->decimal('plame_aporte_empleador_0804_essalud', 10, 2)->default(0);
            $table->decimal('plame_aporte_empleador_0805_sctr', 10, 2)->default(0);
            $table->decimal('plame_aporte_empleador_0810_eps', 10, 2)->default(0);

            $table->timestamps();


            $table->foreign('plan_mensual_id')->references('id')->on('plan_mensuales')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('plan_mensual_personals');
    }
};

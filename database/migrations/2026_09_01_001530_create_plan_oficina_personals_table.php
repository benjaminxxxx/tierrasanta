<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plan_oficina_personals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('plan_mensual_id')->constrained('plan_mensuales')->cascadeOnDelete();
            $table->foreignId('plan_empleado_id')->constrained('plan_empleados');
            $table->unsignedInteger('orden')->default(0);
            $table->string('nombres');
            $table->string('cargo')->nullable();
            $table->string('tipo_ingreso', 20)->default('planilla');
            $table->boolean('beneficios_mensuales')->default(false);
            $table->string('sistema_pension', 20)->nullable();
            $table->boolean('es_pensionista')->default(false);
            $table->unsignedSmallInteger('edad')->nullable();
            $table->decimal('remuneracion_basica', 10, 2)->default(0.00);
            $table->decimal('asignacion_familiar', 10, 2)->default(0.00);
            $table->string('cuenta_principal')->nullable();
            $table->string('cuenta_secundaria')->nullable();
            $table->string('comprobante')->nullable();
            $table->unsignedTinyInteger('dias_mes')->default(30);
            $table->unsignedTinyInteger('dias_vacaciones')->default(0);
            $table->unsignedTinyInteger('dias_suspension_perfecta')->default(0);
            $table->unsignedTinyInteger('dias_laborados')->default(0);
            $table->decimal('rem_sueldo', 10, 2)->default(0.00);
            $table->decimal('rem_vacaciones', 10, 2)->default(0.00);
            $table->decimal('rem_asignacion_familiar', 10, 2)->default(0.00);
            $table->decimal('total_remuneracion', 10, 2)->default(0.00);
            $table->decimal('desc_afp_fondo', 10, 2)->default(0.00);
            $table->decimal('desc_afp_comision', 10, 2)->default(0.00);
            $table->decimal('desc_afp_prima', 10, 2)->default(0.00);
            $table->decimal('desc_snp', 10, 2)->default(0.00);
            $table->decimal('desc_renta_quinta', 10, 2)->default(0.00);
            $table->decimal('desc_renta_cuarta', 10, 2)->default(0.00);
            $table->decimal('total_descuentos', 10, 2)->default(0.00);
            $table->decimal('neto_planilla', 10, 2)->default(0.00);
            $table->decimal('aporte_essalud', 10, 2)->default(0.00);
            $table->decimal('aporte_vida_ley', 10, 2)->default(0.00);
            $table->decimal('provision_cts', 10, 2)->default(0.00);
            $table->decimal('provision_gratificacion', 10, 2)->default(0.00);
            $table->decimal('gratificacion', 10, 2)->default(0.00);
            $table->decimal('bonif_extraordinaria', 10, 2)->default(0.00);
            $table->decimal('cts', 10, 2)->default(0.00);
            $table->decimal('beneficios_pagados', 10, 2)->default(0.00);
            $table->decimal('sueldo_real', 10, 2)->nullable();
            $table->decimal('bonificacion_negro', 10, 2)->default(0.00);
            $table->decimal('pago_blanco_mes', 10, 2)->default(0.00);
            $table->decimal('costo_contable', 10, 2)->default(0.00);
            $table->decimal('costo_total', 10, 2)->default(0.00);
            $table->json('ajustes')->nullable();
            $table->json('calculados')->nullable();
            $table->timestamps();

            $table->unique(['plan_mensual_id', 'plan_empleado_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plan_oficina_personals');
    }
};

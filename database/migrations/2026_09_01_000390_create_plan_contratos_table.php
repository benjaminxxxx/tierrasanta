<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plan_contratos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('plan_empleado_id')->constrained('plan_empleados')->cascadeOnDelete();
            $table->enum('tipo_contrato', ['plazo fijo', 'indefinido', 'temporal'])->default('indefinido');
            $table->date('fecha_inicio');
            $table->date('fecha_fin')->nullable();
            $table->string('grupo_codigo')->nullable();
            $table->decimal('compensacion_vacacional', 10, 2)->nullable();
            $table->decimal('remuneracion_basica', 10, 2)->nullable();
            $table->decimal('bonificacion', 10, 2)->nullable();
            $table->enum('tipo_planilla', ['agraria', 'oficina', 'general', 'mype', 'construccion'])->default('agraria');
            $table->string('tipo_ingreso', 20)->default('planilla');
            $table->boolean('suspension_cuarta')->default(false);
            $table->boolean('beneficios_mensuales')->default(false);
            $table->string('plan_sp_codigo')->nullable();
            $table->boolean('esta_jubilado')->default(false);
            $table->enum('modalidad_pago', ['mensual', 'quincenal', 'semanal'])->default('mensual');
            $table->string('metodo_pago', 20)->nullable();
            $table->string('banco', 40)->nullable();
            $table->string('tipo_cuenta', 20)->nullable();
            $table->string('moneda_cuenta', 3)->nullable();
            $table->string('numero_cuenta', 40)->nullable();
            $table->string('banco_secundario', 40)->nullable();
            $table->string('tipo_cuenta_secundaria', 20)->nullable();
            $table->string('moneda_cuenta_secundaria', 3)->nullable();
            $table->string('numero_cuenta_secundaria', 40)->nullable();
            $table->text('motivo_despido')->nullable();
            $table->foreignId('creado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('actualizado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->date('fecha_fin_prueba')->nullable();
            $table->string('motivo_cese_sunat', 2)->nullable();
            $table->text('comentario_cese')->nullable();
            $table->foreignId('finalizado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('estado', ['activo', 'en_prueba', 'finalizado'])->default('activo');
            $table->foreignId('eliminado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->softDeletes();

            $table->foreign('grupo_codigo')->references('codigo')->on('plan_grupos')->nullOnDelete();
            $table->foreign('plan_sp_codigo')->references('codigo')->on('plan_sp_desc')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plan_contratos');
    }
};

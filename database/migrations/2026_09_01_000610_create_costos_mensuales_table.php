<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('costos_mensuales', function (Blueprint $table) {
            $table->id();
            $table->integer('anio');
            $table->integer('mes');
            $table->decimal('fijo_administrativo_blanco', 10, 2)->nullable();
            $table->decimal('fijo_administrativo_negro', 10, 2)->nullable();
            $table->decimal('fijo_financiero_blanco', 10, 2)->nullable();
            $table->decimal('fijo_financiero_negro', 10, 2)->nullable();
            $table->decimal('fijo_gastos_oficina_blanco', 10, 2)->nullable();
            $table->decimal('fijo_gastos_oficina_negro', 10, 2)->nullable();
            $table->decimal('fijo_depreciaciones_blanco', 10, 2)->nullable();
            $table->decimal('fijo_depreciaciones_negro', 10, 2)->nullable();
            $table->decimal('fijo_costo_terreno_blanco', 10, 2)->nullable();
            $table->decimal('fijo_costo_terreno_negro', 10, 2)->nullable();
            $table->decimal('operativo_servicios_fundo_blanco', 10, 2)->nullable();
            $table->decimal('operativo_servicios_fundo_negro', 10, 2)->nullable();
            $table->decimal('operativo_mano_obra_indirecta_blanco', 10, 2)->nullable();
            $table->decimal('operativo_mano_obra_indirecta_negro', 10, 2)->nullable();
            $table->timestamp('moi_calculado_en')->nullable();
            $table->foreignId('moi_calculado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->string('moi_archivo')->nullable();
            $table->boolean('moi_concluyente')->nullable();
            $table->json('moi_resumen')->nullable();
            $table->decimal('costo_planilla', 10, 2)->nullable();
            $table->decimal('costo_cuadrilla', 10, 2)->nullable();
            $table->decimal('costo_cuadrilla_bono', 12, 2)->nullable()->default(0.00);
            $table->decimal('costo_maquinaria', 10, 2)->nullable();
            $table->decimal('costo_pesticida', 10, 2)->nullable();
            $table->decimal('costo_fertilizante', 10, 2)->nullable();
            $table->decimal('costo_servicio_campo', 12, 2)->nullable()->default(0.00);
            $table->decimal('costo_gastos_generales', 10, 2)->nullable();
            $table->decimal('costo_bono_productividad', 12, 2)->nullable()->default(0.00);
            $table->decimal('costo_planilla_calculado', 10, 2)->nullable();
            $table->decimal('costo_mano_obra_indirecta', 12, 2)->nullable();
            $table->decimal('costo_cuadrilla_calculado', 10, 2)->nullable();
            $table->decimal('costo_cuadrilla_bono_calculado', 12, 2)->nullable()->default(0.00);
            $table->decimal('costo_maquinaria_calculado', 10, 2)->nullable();
            $table->decimal('costo_pesticida_calculado', 10, 2)->nullable();
            $table->decimal('costo_fertilizante_calculado', 10, 2)->nullable();
            $table->decimal('costo_servicio_campo_calculado', 12, 2)->nullable()->default(0.00);
            $table->decimal('costo_gastos_generales_calculado', 10, 2)->nullable();
            $table->decimal('costo_bono_productividad_calculado', 12, 2)->nullable()->default(0.00);
            $table->decimal('fdm_costo_bonificacion', 12, 2)->nullable()->default(0.00);
            $table->string('reporte_file')->nullable();
            $table->enum('estado', ['pendiente', 'consolidado', 'con_diferencias'])->default('pendiente');
            $table->timestamp('calculado_en')->nullable();
            $table->foreignId('calculado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->text('observaciones')->nullable();
            $table->timestamps();

            $table->unique(['anio', 'mes']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('costos_mensuales');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('resumen_costo_diarios', function (Blueprint $table) {
            $table->id();
            $table->string('campania', 50);
            $table->date('fecha');
            $table->string('origen_tipo', 50);
            $table->unsignedBigInteger('origen_id')->nullable();
            $table->string('campo', 50)->nullable();
            $table->unsignedBigInteger('labor')->nullable();
            $table->string('trabajador')->nullable();
            $table->unsignedBigInteger('plan_empleado_id')->nullable();
            $table->string('cuadrilla_grupo_id', 64)->nullable();
            $table->decimal('tipo_cambio', 8, 4)->default(1.0000);
            $table->unsignedSmallInteger('minutos')->nullable()->default(0);
            $table->decimal('cantidad_jornales', 6, 4)->nullable()->default(0.0000);
            $table->string('insumo_nombre', 150)->nullable();
            $table->string('orden_compra', 50)->nullable();
            $table->string('factura', 50)->nullable();
            $table->string('tienda_comercial', 100)->nullable();
            $table->decimal('cantidad_insumo', 10, 2)->nullable()->default(0.00);
            $table->decimal('costo_total', 24, 14)->default(0.00000000000000);
            $table->string('labor_nombre', 150)->nullable();
            $table->string('observacion')->nullable();
            $table->timestamps();

            $table->index(['campo', 'fecha'], 'resumen_costo_campo_fecha_index');
            $table->index(['campania', 'campo']);
            $table->index(['campania', 'fecha']);
            $table->index(['campania', 'origen_tipo']);
            $table->index('cuadrilla_grupo_id');
            $table->index(['plan_empleado_id', 'fecha'], 'resumen_costo_empleado_fecha_index');
            $table->index(['origen_tipo', 'fecha'], 'resumen_costo_origen_fecha_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('resumen_costo_diarios');
    }
};

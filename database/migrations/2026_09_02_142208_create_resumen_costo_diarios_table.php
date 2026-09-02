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
        Schema::create('resumen_costo_diarios', function (Blueprint $table) {
            $table->id();

            // Identificación de Campaña y Fecha (Sin FK para proteger la data histórica)
            $table->string('campania', 50);
            $table->date('fecha');

            // Origen del registro: 'planilla', 'cuadrilla', 'maquinaria', 'fertilizante', 'pesticida', 'costo_fijo', 'riego'
            $table->string('origen_tipo', 50);
            $table->unsignedBigInteger('origen_id')->nullable(); // ID simple de la tabla fuente sin constraints

            // Identificadores de Clasificación Agrícola (IDs simples sin relaciones estrictas)
            $table->string('campo', 50)->nullable();
            $table->unsignedBigInteger('labor')->nullable();
            $table->string('trabajador', 255)->nullable();

            // Identificador de Grupo/Lote (para agrupar cuadrillas o jornadas sin usar tablas externas)
            $table->string('cuadrilla_grupo_id', 64)->nullable();

            // Tipo de Cambio
            $table->decimal('tipo_cambio', 8, 4)->default(1.0000);

            // Métricas de Mano de Obra
            $table->decimal('horas', 6, 2)->default(0.00);
            $table->decimal('cantidad_jornales', 6, 4)->default(0.00);

            // Métricas de Insumos / Servicios / Compras
            $table->string('insumo_nombre', 150)->nullable();
            $table->string('orden_compra', 50)->nullable();
            $table->string('factura', 50)->nullable();
            $table->string('tienda_comercial', 100)->nullable();
            $table->decimal('cantidad_insumo', 10, 2)->default(0.00);

            // Costo Total Calculado (Moneda Base)
            $table->decimal('costo_total', 12, 2)->default(0.00);
            $table->string('labor_nombre', 150)->nullable();
            $table->string('observacion', 255)->nullable();

            $table->timestamps();

            // Índices prioritarios para acelerar recálculos masivos (Delete / Select con WHERE)
            $table->index(['campania', 'campo']);
            $table->index(['campania', 'origen_tipo']);
            $table->index(['campania', 'fecha']);
            $table->index('cuadrilla_grupo_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('resumen_costo_diarios');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cuad_gastos_grupos', function (Blueprint $table) {
            $table->id();
            $table->decimal('monto', 10, 2);
            $table->string('descripcion');
            $table->boolean('esta_pagado')->default(false);
            $table->year('anio_contable')->nullable();
            $table->tinyInteger('mes_contable')->nullable();
            $table->string('codigo_grupo');
            $table->timestamp('fecha_gasto')->useCurrent();
            $table->foreignId('cuad_tramo_laboral_id')->nullable()->constrained('cuad_tramos_laborales')->restrictOnDelete();
            $table->enum('estado', ['pendiente', 'aprobado', 'en_correccion'])->default('pendiente');
            $table->foreignId('desglose_detalle_id')->nullable()->constrained('desglose_detalles')->nullOnDelete();
            $table->foreignId('creado_por')->nullable()->constrained('users');
            $table->foreignId('aprobado_por')->nullable()->constrained('users');
            $table->timestamp('aprobado_en')->nullable();
            $table->foreignId('habilitado_por')->nullable()->constrained('users');
            $table->timestamp('habilitado_en')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cuad_gastos_grupos');
    }
};

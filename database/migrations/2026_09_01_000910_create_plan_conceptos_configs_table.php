<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plan_conceptos_configs', function (Blueprint $table) {
            $table->id();
            $table->string('codigo_sunat', 4)->nullable();
            $table->string('nombre');
            $table->string('abreviatura_excel', 15);
            $table->enum('clase', ['ingreso', 'descuento', 'aporte_patronal']);
            $table->enum('origen', ['blanco', 'negro']);
            $table->enum('metodo_calculo', ['porcentaje', 'monto_fijo', 'manual']);
            $table->decimal('valor_base', 10, 4);
            $table->boolean('incluye_igv')->default(false);
            $table->date('fecha_inicio');
            $table->date('fecha_fin')->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plan_conceptos_configs');
    }
};

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
        Schema::create('plan_conceptos_configs', function (Blueprint $table) {
            $table->id();
            $table->string('codigo_sunat', 4)->nullable(); // Ej: 0803, 0121, 0601
            $table->string('nombre');                      // Ej: Vida Ley
            $table->string('abreviatura_excel', 15);       // Ej: V_LEY

            // Clasificación
            $table->enum('clase', ['ingreso', 'descuento', 'aporte_patronal']);
            $table->enum('origen', ['blanco', 'negro']);

            // Lógica de cálculo
            $table->enum('metodo_calculo', ['porcentaje', 'monto_fijo', 'manual']);
            $table->decimal('valor_base', 10, 4);          // Ej: 0.63 (porcentaje) o 100 (fijo)
            $table->boolean('incluye_igv')->default(false); // Para el caso de Vida Ley

            // Trazabilidad
            $table->date('fecha_inicio');
            $table->date('fecha_fin')->nullable();         // Si es null, sigue vigente

            $table->boolean('activo')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('plan_conceptos_configs');
    }
};

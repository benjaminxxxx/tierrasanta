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
        Schema::create('costo_mensual_distribuciones', function (Blueprint $table) {
            $table->id();

            $table->foreignId('costo_mensual_id')
                ->constrained('costos_mensuales')
                ->cascadeOnDelete();

            $table->foreignId('campo_campania_id')
                ->constrained('campos_campanias')
                ->cascadeOnDelete();

            $table->unsignedSmallInteger('anio');
            $table->unsignedTinyInteger('mes');

            $table->unsignedTinyInteger('dias_mes');
            $table->unsignedTinyInteger('dias_activos');
            $table->decimal('porcentaje', 20, 10);

            // Costos fijos
            $table->decimal('fijo_administrativo', 20, 10)->default(0);
            $table->decimal('fijo_financiero', 20, 10)->default(0);
            $table->decimal('fijo_gastos_oficina', 20, 10)->default(0);
            $table->decimal('fijo_depreciaciones', 20, 10)->default(0);
            $table->decimal('fijo_costo_terreno', 20, 10)->default(0);

            // Costos operativos
            $table->decimal('operativo_servicios_fundo', 20, 10)->default(0);
            $table->decimal('operativo_mano_obra_indirecta', 20, 10)->default(0);

            $table->timestamps();

            $table->unique([
                'costo_mensual_id',
                'campo_campania_id'
            ], 'uq_costo_mes_campania');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('costo_mensual_distribuciones');
    }
};

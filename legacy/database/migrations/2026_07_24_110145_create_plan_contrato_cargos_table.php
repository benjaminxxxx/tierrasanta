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
        Schema::create('plan_empleado_cargos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('plan_empleado_id')->constrained('plan_empleados')->onDelete('cascade');
            $table->foreignId('plan_cargo_id')->constrained('plan_cargos');
            $table->string('grupo_codigo')->nullable(); // se mueve aquí, cambia junto con el cargo
            $table->dateTime('fecha_inicio');
            $table->dateTime('fecha_fin')->nullable(); // null = vigente
            $table->string('motivo_cambio')->nullable(); // 'ascenso','ingreso','rotacion'
            $table->string('creado_por')->nullable();
            $table->timestamps();

            $table->index(['plan_cargo_id', 'fecha_fin']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('plan_empleado_cargos');
    }
};

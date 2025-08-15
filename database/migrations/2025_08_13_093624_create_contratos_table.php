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
        Schema::create('contratos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empleado_id')->constrained('empleados')->onDelete('restrict');
            $table->enum('tipo_contrato',['plazo fijo','indefinido','temporal'])->default('indefinido');
            $table->date('fecha_inicio');
            $table->date('fecha_fin')->nullable();
            $table->decimal('sueldo', 10, 2)->nullable();//un nuevo sueldo determinara ahora un nuevo contrato
            $table->string('cargo_codigo')->nullable(); //la tabla foreign tiene como primary key string
            $table->foreign('cargo_codigo')->references('codigo')->on('cargos')->onDelete('set null'); // Clave foránea
            $table->string('grupo_codigo')->nullable(); //la tabla foreign tiene como primary key string
            $table->foreign('grupo_codigo')->references('codigo')->on('grupos')->onDelete('set null');
            $table->decimal('compensacion_vacacional', 10, 2)->nullable();
            $table->tinyInteger('tipo_planilla')->default(1);            
            $table->string('descuento_sp_id')->nullable();
            $table->foreign('descuento_sp_id')->references('codigo')->on('descuento_sp')->onDelete('set null');
            $table->boolean('esta_jubilado')->default(false);
            $table->enum('modalidad_pago',['mensual','quincenal','semanal'])->default('mensual');
            $table->text('motivo_despido')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contratos');
    }
};

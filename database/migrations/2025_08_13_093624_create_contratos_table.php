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
        Schema::create('plan_contratos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('plan_empleado_id')->constrained('plan_empleados')->onDelete('cascade');
            $table->enum('tipo_contrato',['plazo fijo','indefinido','temporal'])->default('indefinido');
            $table->date('fecha_inicio');
            $table->date('fecha_fin')->nullable();
            $table->decimal('sueldo', 10, 2)->nullable();//un nuevo sueldo determinara ahora un nuevo contrato
            $table->string('cargo_codigo')->nullable(); //la tabla foreign tiene como primary key string
            $table->foreign('cargo_codigo')->references('codigo')->on('plan_cargos')->onDelete('set null'); // Clave foránea
            $table->string('grupo_codigo')->nullable(); //la tabla foreign tiene como primary key string
            $table->foreign('grupo_codigo')->references('codigo')->on('plan_grupos')->onDelete('set null');
            $table->decimal('compensacion_vacacional', 10, 2)->nullable();
            $table->enum('tipo_planilla', ['agraria', 'oficina', 'general', 'mype', 'construccion'])->default('agraria');  //hay agraria, y de oficina y podria haber mas         
            $table->string('plan_sp_codigo')->nullable();
            $table->foreign('plan_sp_codigo')->references('codigo')->on('plan_sp_desc')->onDelete('set null');
            $table->boolean('esta_jubilado')->default(false);
            $table->enum('modalidad_pago',['mensual','quincenal','semanal'])->default('mensual');
            $table->text('motivo_despido')->nullable();
            $table->foreignId('creado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('actualizado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('plan_contratos');
    }
};

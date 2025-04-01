<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('reporte_diarios', function (Blueprint $table) {
            $table->id();
            $table->string('documento'); // campo para el documento del empleado
            $table->string('empleado_nombre'); // nombre del empleado
            $table->string('asistencia', 6); // asistencia (varchar 6)
            $table->string('tipo_trabajador', 20); // tipo de trabajador (varchar 20)
            $table->time('total_horas'); // total de horas (time)
            $table->date('fecha'); // fecha
            $table->integer('orden')->nullable(); 
            $table->timestamps(); // timestamps para created_at y updated_at
            $table->decimal('bono_productividad', 10, 2)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reporte_diarios');
    }
};

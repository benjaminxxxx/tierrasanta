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
        Schema::create('reporte_costo_planillas', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('campos_campanias_id');
            $table->string('campo',10);
            $table->string('documento',20);
            $table->string('empleado_nombre',255);
            $table->date('fecha');
            $table->time('horas_totales')->nullable();
            $table->time('hora_inicio')->nullable();
            $table->time('hora_salida')->nullable();
            $table->decimal('factor',12,8);
            $table->time('hora_diferencia')->nullable();
            $table->decimal('hora_diferencia_entero',8,2)->nullable();
            $table->decimal('costo_hora',12,8);
            $table->decimal('gasto',12,8);
            $table->decimal('gasto_bono',12,8);
            $table->text('labor')->nullable(); 
            $table->timestamps();

            $table->foreign('campos_campanias_id', 'fk_campania_reporte_pl')
                ->references('id')->on('campos_campanias')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reporte_costo_planillas');
    }
};

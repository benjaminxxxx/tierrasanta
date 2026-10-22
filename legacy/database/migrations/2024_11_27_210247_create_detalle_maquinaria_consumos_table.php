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
        Schema::create('detalle_maquinaria_consumos', function (Blueprint $table) {
            $table->id();
            $table->date('fecha')->comment('Fecha del consumo');
            $table->time('hora_inicio')->nullable()->comment('Hora de inicio del uso de la maquinaria');
            $table->time('hora_salida')->nullable()->comment('Hora de salida o finalización');
            $table->decimal('total_horas', 8, 2)->nullable()->comment('Total de horas trabajadas');
            $table->string('campo', 255)->nullable()->comment('Campo donde se utilizó la maquinaria');
            $table->decimal('cantidad_combustible', 10, 3)->comment('Cantidad de combustible consumido');
            $table->decimal('costo_combustible', 10, 2)->comment('Costo total del combustible');
            $table->text('descripcion_labor')->nullable()->comment('Descripción de la labor realizada');
            $table->decimal('ratio', 10, 3)->nullable()->comment('Relación o eficiencia del uso de combustible');
            $table->decimal('valor_costo', 10, 2)->nullable()->comment('Valor del costo total por la operación');
            $table->foreignId('maquinaria_id') // Relación con la tabla maquinarias
                ->constrained('maquinarias') // Hace referencia automáticamente al campo id de maquinarias
                ->onDelete('cascade');
            $table->timestamps(); // created_at, updated_at
            
            $table->unsignedBigInteger('almacen_producto_salida_id');

            $table->foreign('almacen_producto_salida_id')->references('id')->on('almacen_producto_salidas')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('detalle_maquinaria_consumos');
    }
};

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
        Schema::create('distribucion_combustibles', function (Blueprint $table) {
            $table->id();
            $table->date('fecha');
            $table->string('campo');
            $table->time('hora_inicio');
            $table->time('hora_salida');
            $table->integer('horas')->nullable();
            $table->decimal('cantidad_combustible', 10, 2)->nullable();
            $table->decimal('costo_combustible', 10, 4)->nullable();
            $table->string('actividad'); // Actividad
            $table->string('maquinaria_nombre')->nullable();
            $table->decimal('ratio', 10, 4)->nullable();
            $table->decimal('valor_costo', 10, 4)->nullable();

            $table->foreignId('maquinaria_id')
            ->constrained('maquinarias')
            ->onDelete('cascade');
            $table->unsignedBigInteger('almacen_producto_salida_id')->nullable();

            $table->foreign('almacen_producto_salida_id')->references('id')->on('almacen_producto_salidas')->onDelete('set null');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('distribucion_combustibles');
    }
};

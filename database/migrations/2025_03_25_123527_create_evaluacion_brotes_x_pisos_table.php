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
        Schema::create('evaluacion_brotes_x_pisos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('campania_id');
            $table->date('fecha')->nullable();
            $table->decimal('metros_cama', 10, 3);
            $table->string('evaluador', 255);
            $table->foreignId('empleado_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('cuadrillero_id')->nullable()->constrained('cuadrilleros')->onDelete('set null');
            $table->integer('promedio_actual_brotes_2piso')->nullable();
            $table->integer('promedio_brotes_2piso_n_dias')->nullable();
            $table->integer('promedio_actual_brotes_3piso')->nullable();
            $table->integer('promedio_brotes_3piso_n_dias')->nullable();
            $table->integer('promedio_actual_total_brotes_2y3piso')->nullable();
            $table->integer('promedio_total_brotes_2y3piso_n_dias')->nullable();
            $table->text('reporte_file')->nullable();
            $table->foreign('campania_id')->references('id')->on('campos_campanias')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('evaluacion_brotes_x_pisos');
    }
};

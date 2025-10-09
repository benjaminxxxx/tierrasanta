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
        Schema::create('poblacion_plantas', function (Blueprint $table) {
            $table->id();
            $table->decimal('area_lote', 10, 3);
            $table->decimal('metros_cama', 10, 3);
            $table->string('evaluador', 255);
            $table->date('fecha')->nullable();
            $table->enum('tipo_evaluacion',['dia_cero','resiembra']);
            $table->unsignedBigInteger('campania_id');
            $table->timestamps();

            // Claves foráneas
            $table->foreignId('empleado_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('cuadrillero_id')->nullable()->constrained('cuad_cuadrilleros')->onDelete('set null');
            $table->foreign('campania_id')->references('id')->on('campos_campanias')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('poblacion_plantas');
    }
};

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
        Schema::create('dias', function (Blueprint $table) {
            $table->id();
            $table->unsignedTinyInteger('dia'); // Día del mes
            $table->unsignedTinyInteger('mes'); // Mes
            $table->year('anio'); // Año
            $table->boolean('es_dia_no_laborable')->default(false); // Indica si es feriado o no laboral
            $table->boolean('es_dia_domingo')->default(false); // Indica si es domingo
            $table->text('observaciones')->nullable(); // Observaciones adicionales
            $table->timestamps();
    
            // Asegurar que la combinación de día, mes y año sea única
            $table->unique(['dia', 'mes', 'anio']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dias');
    }
};

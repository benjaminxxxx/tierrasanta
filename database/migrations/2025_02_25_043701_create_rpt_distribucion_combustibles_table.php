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
        Schema::create('rpt_distribucion_combustibles', function (Blueprint $table) {
            $table->id();
            $table->integer('mes');
            $table->integer('anio');
            $table->string('file_blanco')->nullable(); // Ruta del archivo Excel guardado
            $table->string('file_negro')->nullable();
            $table->decimal('total_combustible', 10, 2)->nullable();
            $table->decimal('total_costo', 10, 2)->nullable();
            $table->decimal('total_horas', 10, 2)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rpt_distribucion_combustibles');
    }
};

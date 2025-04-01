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
        Schema::create('detalle_riegos', function (Blueprint $table) {
            $table->id();
            $table->string('campo');
            $table->string('regador');
            $table->date('fecha');
            $table->time('hora_inicio');
            $table->time('hora_fin');
            $table->time('total_horas');
            $table->boolean('sh')->default(false);
            $table->timestamps();

            // Índice para optimizar búsquedas por campo y fecha
            $table->index(['campo', 'hora_inicio']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('detalle_riegos');
    }
};

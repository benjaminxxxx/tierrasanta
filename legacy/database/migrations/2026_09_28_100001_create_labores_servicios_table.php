<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Catálogo de sugerencias de labores realizadas por servicios externos.
     * Independiente de labores y labores_riego; los detalles guardan el texto.
     */
    public function up(): void
    {
        Schema::create('labores_servicios', function (Blueprint $table) {
            $table->id();
            $table->string('nombre')->unique();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('labores_servicios');
    }
};

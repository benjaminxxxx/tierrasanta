<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Catálogo de sugerencias de nombres de servicio (Cargador frontal, Retroexcavadora...).
     * Los registros de servicios guardan el texto, no el id: esta tabla solo sirve para sugerir.
     */
    public function up(): void
    {
        Schema::create('servicios_nombres', function (Blueprint $table) {
            $table->id();
            $table->string('nombre')->unique();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('servicios_nombres');
    }
};

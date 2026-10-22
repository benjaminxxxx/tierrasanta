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
        Schema::create('empresa', function (Blueprint $table) {
            $table->string('ruc', 11)->primary(); // Campo 'ruc' de longitud 11 como clave primaria
            $table->string('razon_social', 255); // Campo 'razon_social' para la denominación o razón social
            $table->string('establecimiento', 255); // Campo 'establecimiento' para la dirección
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('empresa');
    }
};

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
        Schema::create('sunat_tabla5_tipo_existencias', function (Blueprint $table) {
            $table->string('codigo', 2)->primary(); // Campo 'codigo' de longitud 2 como clave primaria
            $table->string('descripcion', 255); // Campo 'descripcion' de longitud 255
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sunat_tabla5_tipo_existencias');
    }
};

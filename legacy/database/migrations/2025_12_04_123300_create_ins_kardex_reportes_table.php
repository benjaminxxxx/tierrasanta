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
        Schema::create('ins_kardex_reportes', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 255); // Nombre del reporte
            $table->unsignedSmallInteger('anio'); // Año del reporte
            $table->enum('estado', ['borrador', 'vigente', 'cerrado'])->default('borrador');
            $table->enum('tipo_kardex', ['blanco', 'negro'])->nullable(); // Filtrado opcional
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ins_kardex_reportes');
    }
};

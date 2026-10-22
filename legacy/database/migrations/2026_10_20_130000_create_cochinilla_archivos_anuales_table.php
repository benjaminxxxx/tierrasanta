<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Archivo Excel oficial de cochinilla subido por año (historial). El último de cada año es la base al exportar: de él
 * salen las hojas que el sistema aún no importa (Despachos, Stock, Kardex Físico…), así no se pierden.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('cochinilla_archivos_anuales', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('anio')->index();
            $table->string('ruta');
            $table->string('nombre_original')->nullable();
            $table->json('resumen')->nullable();
            $table->foreignId('subido_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cochinilla_archivos_anuales');
    }
};

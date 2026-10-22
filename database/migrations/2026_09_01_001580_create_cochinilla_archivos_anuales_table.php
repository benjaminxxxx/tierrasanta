<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cochinilla_archivos_anuales', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('anio');
            $table->string('ruta');
            $table->string('nombre_original')->nullable();
            $table->json('resumen')->nullable();
            $table->foreignId('subido_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('anio');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cochinilla_archivos_anuales');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kardex_cargas', function (Blueprint $table) {
            $table->id();
            $table->string('archivo');
            $table->string('nombre_original');
            $table->unsignedSmallInteger('anio');
            $table->string('tipo_kardex', 10);
            $table->unsignedInteger('version_archivo')->default(1);
            $table->unsignedBigInteger('subido_por')->nullable();
            $table->string('subido_por_nombre')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kardex_cargas');
    }
};

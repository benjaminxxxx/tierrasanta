<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cuad_tramo_cuadrilleros', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('cuadrillero_id');
            $table->unsignedBigInteger('cuad_tramo_laboral_grupo_id');
            $table->string('nombres');
            $table->unsignedInteger('orden');
            $table->timestamps();

            $table->unique(['cuadrillero_id', 'cuad_tramo_laboral_grupo_id'], 'unq_tramo_cuadri_grupo');

            $table->foreign('cuadrillero_id', 'fk_tramo_cuad_cuadri')->references('id')->on('cuad_cuadrilleros')->restrictOnDelete();
            $table->foreign('cuad_tramo_laboral_grupo_id', 'fk_tramo_cuad_grupo')->references('id')->on('cuad_tramo_grupos')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cuad_tramo_cuadrilleros');
    }
};

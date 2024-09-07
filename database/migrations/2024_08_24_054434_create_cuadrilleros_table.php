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
        Schema::create('cuadrilleros', function (Blueprint $table) {
            $table->id();
            $table->string('nombre_completo');
            $table->string('codigo_grupo');
            $table->string('dni')->nullable();
            $table->string('codigo')->nullable()->unique();
            $table->timestamps();
            $table->foreign('codigo_grupo')->references('codigo')->on('grupos_cuadrilla')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cuadrilleros');
    }
};

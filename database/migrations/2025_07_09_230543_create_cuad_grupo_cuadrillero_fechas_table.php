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
        Schema::create('cuad_grupo_cuadrillero_fechas', function (Blueprint $table) {
            $table->id();
            $table->string('codigo_grupo');
            $table->unsignedBigInteger('cuadrillero_id');
            $table->date('fecha');

            $table->timestamps();

            // Clave única compuesta para evitar duplicados
            $table->unique(['codigo_grupo', 'cuadrillero_id', 'fecha'], 'grupo_cuadrillero_fecha_unique');

            // Relaciones sugeridas (ajústalas si tienes FK en tu esquema)
            $table->foreign('codigo_grupo')
                ->references('codigo')
                ->on('cua_grupos')
                ->onDelete('cascade');

            $table->foreign('cuadrillero_id')
                ->references('id')
                ->on('cuadrilleros')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cuad_grupo_cuadrillero_fechas');
    }
};

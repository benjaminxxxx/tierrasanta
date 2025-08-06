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
        Schema::create('cuad_grupo_orden', function (Blueprint $table) {
            $table->date('fecha'); // Primer día del periodo (ej: semana)
            $table->string('codigo_grupo');
            $table->unsignedInteger('orden'); // Orden en que aparece ese grupo esa semana

            // Clave primaria compuesta
            $table->primary(['fecha', 'codigo_grupo']);

            // FK hacia cua_grupos.codigo
            $table->foreign('codigo_grupo')
                ->references('codigo')
                ->on('cua_grupos')
                ->onDelete('cascade');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cuad_grupo_orden');
    }
};

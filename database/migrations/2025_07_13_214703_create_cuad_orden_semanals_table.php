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
         Schema::create('cuad_orden_semanal', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('cuadrillero_id');
            $table->date('fecha_inicio'); // lunes de la semana
            $table->unsignedInteger('orden');
            $table->string('codigo_grupo', 30)->nullable();
            $table->timestamps();

            $table->unique(['cuadrillero_id', 'fecha_inicio']);
            $table->foreign('codigo_grupo')
                ->references('codigo')
                ->on('cua_grupos')
                ->onDelete('set null');
            $table->foreign('cuadrillero_id')->references('id')->on('cuadrilleros')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cuad_orden_semanal');
    }
};

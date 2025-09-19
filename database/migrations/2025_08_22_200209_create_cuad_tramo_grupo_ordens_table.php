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
        Schema::create('cuad_tramo_laboral_grupos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('cuad_tramo_laboral_id'); // FK hacia tramo
            $table->string('codigo_grupo'); // FK hacia grupos
            $table->unsignedInteger('orden'); // Orden del grupo dentro del tramo
            $table->timestamps();

            $table->foreign('cuad_tramo_laboral_id')
                ->references('id')
                ->on('cuad_tramo_laborals')
                ->onDelete('cascade');

            $table->foreign('codigo_grupo')
                ->references('codigo')
                ->on('cua_grupos')
                ->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cuad_tramo_laboral_grupos');
    }
};

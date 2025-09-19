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
        Schema::create('cuad_tramo_laboral_cuadrilleros', function (Blueprint $table) {
            $table->id();

            // Relación con cuadrillero
            $table->foreignId('cuadrillero_id')
                ->constrained('cuadrilleros')
                ->cascadeOnDelete();

            // Relación con tramo laboral grupo (nombre de constraint reducido manualmente)
            $table->unsignedBigInteger('cuad_tramo_laboral_grupo_id');
            $table->foreign('cuad_tramo_laboral_grupo_id', 'tramo_grupo_fk')
                ->references('id')
                ->on('cuad_tramo_laboral_grupos')
                ->cascadeOnDelete();

            // Orden dentro del tramo
            $table->unsignedInteger('orden');

            $table->timestamps();

            // Único por cuadrillero+grupo
            $table->unique(['cuadrillero_id', 'cuad_tramo_laboral_grupo_id'], 'cuad_tramo_cuad_grupo_unq');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cuad_tramo_laboral_cuadrilleros');
    }
};

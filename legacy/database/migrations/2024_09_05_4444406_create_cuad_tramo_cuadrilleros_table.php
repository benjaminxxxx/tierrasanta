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
        Schema::create('cuad_tramo_cuadrilleros', function (Blueprint $table) {
            $table->id();

            // Relación con cuadrillero (no se elimina histórico)
            $table->foreignId('cuadrillero_id')
                ->constrained('cuad_cuadrilleros', 'id', 'fk_tramo_cuad_cuadri')
                ->restrictOnDelete();

            // Relación con grupo de tramo laboral
            $table->foreignId('cuad_tramo_laboral_grupo_id')
                ->constrained('cuad_tramo_grupos', 'id', 'fk_tramo_cuad_grupo')
                ->cascadeOnDelete();

            // Datos históricos
            $table->string('nombres');   // Nombre del cuadrillero al momento del registro
            $table->unsignedInteger('orden');

            $table->timestamps();

            // Evita duplicados por cuadrillero + grupo
            $table->unique(
                ['cuadrillero_id', 'cuad_tramo_laboral_grupo_id'],
                'unq_tramo_cuadri_grupo'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cuad_tramo_cuadrilleros');
    }
};

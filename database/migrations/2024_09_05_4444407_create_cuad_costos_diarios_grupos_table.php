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
        Schema::create('cuad_costos_diarios_grupos', function (Blueprint $table) {
            $table->id();
            $table->string('codigo_grupo');
            $table->date('fecha');
            $table->decimal('jornal', 8, 2)->nullable();
            $table->unique(['codigo_grupo', 'fecha']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cuad_costo_diario_grupos');
    }
};

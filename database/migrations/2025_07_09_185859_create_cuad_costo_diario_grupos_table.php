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
        Schema::create('cuad_costo_diario_grupos', function (Blueprint $table) {
            $table->id();
            $table->string('codigo_grupo');
            $table->date('fecha');
            $table->decimal('jornal', 8, 2)->nullable(); // puede ser null si aún no se asignó
            $table->timestamps();

            $table->unique(['codigo_grupo', 'fecha']);
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
        Schema::dropIfExists('cuad_costo_diario_grupos');
    }
};

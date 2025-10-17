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
        Schema::create('cuad_cuadrilleros', function (Blueprint $table) {
            $table->id();
            $table->string('nombres')->unique();
            $table->string('codigo_grupo',30)->nullable();
            $table->string('dni')->nullable()->unique();
            $table->foreignId('creado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('actualizado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('eliminado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            $table->foreign('codigo_grupo')
                ->references('codigo')
                ->on('cuad_grupos')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cuad_cuadrilleros');
    }
};

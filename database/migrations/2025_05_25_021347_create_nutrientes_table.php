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
        Schema::create('nutrientes', function (Blueprint $table) {
            $table->string('codigo')->primary(); // Ej: N, P, K, etc. Clave primaria
            $table->string('nombre'); // Nombre comercial
            $table->string('unidad')->default('%'); // Porcentaje o gramos
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('nutrientes');
    }
};

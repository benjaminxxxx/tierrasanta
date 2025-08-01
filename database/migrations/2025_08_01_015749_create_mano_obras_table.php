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
        Schema::create('mano_obras', function (Blueprint $table) {
            $table->string('codigo')->primary(); // Ej: 'cosecha', 'mantenimiento'
            $table->string('descripcion')->unique(); // Ej: 'Cosecha de producto final'
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mano_obras');
    }
};

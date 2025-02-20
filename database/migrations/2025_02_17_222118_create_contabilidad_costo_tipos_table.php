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
        Schema::create('contabilidad_costo_tipos', function (Blueprint $table) {
            $table->id();
            $table->string('nombre_costo')->unique();
            $table->string('tipo_costo');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contabilidad_costo_tipos');
    }
};

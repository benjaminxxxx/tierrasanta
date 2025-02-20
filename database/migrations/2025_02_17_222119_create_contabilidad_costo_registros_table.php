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
        Schema::create('contabilidad_costo_registros', function (Blueprint $table) {
            $table->id();
            $table->foreignId('nombre_costo_id')->constrained('contabilidad_costo_tipos')->onDelete('cascade');
            $table->date('fecha');
            $table->decimal('valor', 10, 2);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contabilidad_costo_registros');
    }
};

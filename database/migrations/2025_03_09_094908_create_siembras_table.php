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
        Schema::create('siembras', function (Blueprint $table) {
            $table->id();
            $table->string('campo_nombre'); // Relación con Campo
            $table->date('fecha_siembra');
            $table->date('fecha_renovacion')->nullable(); // Puede ser NULL si aún no se ha renovado
            $table->string('variedad_tuna', 50)->nullable();
            $table->string('sistema_cultivo', 255)->nullable();
            $table->decimal('tipo_cambio', 10, 2)->nullable();
            $table->timestamps();

            $table->foreign('campo_nombre')->references('nombre')->on('campos')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('siembras');
    }
};

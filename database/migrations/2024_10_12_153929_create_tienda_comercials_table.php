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
        Schema::create('tienda_comercials', function (Blueprint $table) {
            $table->id();
            $table->string('nombre'); // Nombre de la tienda comercial
            $table->string('ruc', 11)->nullable(); // RUC de la tienda, nullable si no tiene
            $table->string('contacto')->nullable(); // Contacto o responsable, nullable
            $table->timestamps();
        });;
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tienda_comercials');
    }
};

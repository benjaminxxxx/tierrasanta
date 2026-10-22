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
        Schema::create('cochinilla_observaciones', function (Blueprint $table) {
            $table->string('codigo')->primary(); // ejemplo: 'mama', 'infestacion'
            $table->string('descripcion');
            $table->boolean('es_cosecha_mama')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cochinilla_observaciones');
    }
};

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
        Schema::create('cochinilla_venteados', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('lote');
            $table->date('fecha_proceso'); 
            $table->decimal('kilos_ingresado', 10, 2);
            $table->decimal('limpia', 10, 2);
            $table->decimal('basura', 10, 2);
            $table->decimal('polvillo', 10, 2);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cochinilla_venteados');
    }
};

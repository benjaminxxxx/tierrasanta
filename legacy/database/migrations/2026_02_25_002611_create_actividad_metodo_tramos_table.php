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
        Schema::create('actividad_metodo_tramos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('metodo_id')
                ->constrained('actividad_metodos')
                ->cascadeOnDelete();

            // "hasta X kg → S/. Y por kg"
            // null en hasta_kg = "en adelante" (último tramo abierto)
            $table->decimal('hasta', 10, 3)->nullable();
            $table->decimal('monto', 10, 4);

            $table->unsignedTinyInteger('orden')->default(1); // orden del tramo dentro del método

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('actividad_metodo_tramos');
    }
};

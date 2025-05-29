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
        Schema::create('producto_nutrientes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('producto_id')->constrained()->onDelete('cascade');
            $table->string('nutriente_codigo'); // Clave foránea string
            $table->decimal('porcentaje', 5, 2); // Ej: 0.45
            $table->timestamps();

            $table->foreign('nutriente_codigo')
                ->references('codigo')->on('nutrientes')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('producto_nutrientes');
    }
};

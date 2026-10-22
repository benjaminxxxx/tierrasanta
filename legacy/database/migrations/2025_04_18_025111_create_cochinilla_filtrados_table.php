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
        Schema::create('cochinilla_filtrados', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cochinilla_ingreso_id')
                ->nullable()
                ->constrained('cochinilla_ingresos')
                ->nullOnDelete();
            $table->integer('lote');
            $table->date('fecha_proceso');
            $table->decimal('kilos_ingresados', 10, 2);
            $table->decimal('primera', 10, 2);
            $table->decimal('segunda', 10, 2);
            $table->decimal('tercera', 10, 2);
            $table->decimal('piedra', 10, 2);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cochinilla_filtrados');
    }
};

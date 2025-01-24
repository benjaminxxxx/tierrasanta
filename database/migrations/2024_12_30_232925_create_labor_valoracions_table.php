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
        Schema::create('labor_valoracions', function (Blueprint $table) {
            $table->id(); // Identificador único
            $table->foreignId('labor_id') // Clave foránea corta a labores
                ->constrained('labores')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $table->decimal('kg_8', 8, 2); // Kilos estándar para la jornada
            $table->decimal('valor_kg_adicional', 8, 2); // Valor por kilo adicional
            $table->date('vigencia_desde'); 
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('labor_valoracions');
    }
};

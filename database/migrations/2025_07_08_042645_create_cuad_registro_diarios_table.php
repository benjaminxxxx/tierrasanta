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
        Schema::create('cuad_registros_diarios', function (Blueprint $table) {
            $table->id();

            $table->foreignId('cuadrillero_id')
                  ->constrained('cuadrilleros')
                  ->restrictOnDelete();

            $table->date('fecha');
            $table->decimal('costo_personalizado_dia', 10, 2)->nullable();
            $table->boolean('asistencia')->default(true);
            $table->decimal('total_bono', 10, 2)->default(0);
            $table->decimal('costo_dia', 10, 2)->default(0);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cuad_registro_diarios');
    }
};

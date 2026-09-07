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
        Schema::create('cuad_detalles_horas', function (Blueprint $table) {
            $table->id();

            $table->foreignId('registro_diario_id')
                ->constrained('cuad_registros_diarios')
                ->cascadeOnDelete();

            $table->string('campo_nombre');
            $table->integer('codigo_labor')->nullable();

            $table->time('hora_inicio');
            $table->time('hora_fin');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cuad_detalles_horas');
    }
};

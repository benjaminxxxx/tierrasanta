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
        Schema::create('cuad_detalle_horas', function (Blueprint $table) {
            $table->id();

            $table->foreignId('registro_diario_id')
                ->constrained('cuad_registros_diarios')
                ->cascadeOnDelete();

            $table->foreignId('actividad_id')
                ->constrained('actividades')
                ->cascadeOnDelete();

            $table->string('campo_nombre');
            $table->time('hora_inicio');
            $table->time('hora_fin');
            $table->decimal('produccion', 10, 2)->nullable();
            $table->decimal('costo_bono', 10, 2)->default(0);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cuad_detalle_horas');
    }
};

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
        Schema::create('cuad_produccion_actividades', function (Blueprint $table) {
            $table->id();

            // Relación a la tabla de bono
            $table->foreignId('actividad_bono_id')
                ->constrained('cuad_bonos_actividades')
                ->cascadeOnDelete();

            $table->integer('numero_recojo');
            $table->decimal('produccion', 10, 2)->default(0);
            $table->timestamps();

            // Evitar recogidas duplicadas para la misma actividad_bono
            $table->unique(['actividad_bono_id', 'numero_recojo'], 'actividad_bono_recojo_unique');
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cuad_produccion_actividades');
    }
};

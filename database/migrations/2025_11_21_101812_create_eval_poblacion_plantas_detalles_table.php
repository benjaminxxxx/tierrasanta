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
        Schema::create('eval_poblacion_plantas_detalles', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('eval_poblacion_planta_id');

            $table->foreign('eval_poblacion_planta_id', 'fk_eval_det_planta')
                ->references('id')
                ->on('eval_poblacion_plantas')
                ->onDelete('cascade');

            // La cama evaluada: 1,3,5,7,25 etc (elige el ingeniero)
            $table->integer('numero_cama');

            // Longitud debe coincidir siempre entre evaluaciones
            $table->decimal('longitud_cama', 8, 2);

            // Día cero
            $table->integer('eval_cero_plantas_x_hilera');

            // Resiembra
            $table->integer('eval_resiembra_plantas_x_hilera')->nullable();

            $table->timestamps();

            // Evita duplicar la cama en esta evaluación → nombre corto para evitar errores
            $table->unique(
                ['eval_poblacion_planta_id', 'numero_cama'],
                'uq_eval_det_planta_cama'
            );
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('eval_poblacion_plantas_detalles');
    }
};

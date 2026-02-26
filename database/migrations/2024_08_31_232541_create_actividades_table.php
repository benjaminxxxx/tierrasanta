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
        Schema::create('actividades', function (Blueprint $table) {
            $table->id();
            $table->date('fecha');
            $table->string('campo');

            $table->foreignId('labor_id')
                ->nullable()
                ->constrained('labores')
                ->nullOnDelete();

            $table->string('nombre_labor');
            $table->integer('codigo_labor');
            $table->unsignedInteger('recojos')->default(1);
            $table->json('tramos_bonificacion')->nullable();
            $table->json('tramos_bonificacion_destajo')->nullable();
            $table->decimal('estandar_produccion', 10, 2)->nullable();
            $table->string('unidades', 20)->nullable();

            // Control de auditoría
            $table->foreignId('creado_por')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->foreignId('actualizado_por')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('actividades');
    }
};

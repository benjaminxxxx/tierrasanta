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
            $table->json('horarios')->nullable();
            $table->json('tramos_bonificacion')->nullable();
            $table->decimal('estandar_produccion')->nullable();
            $table->decimal('total_horas', 8, 2)->nullable();
            $table->string('unidades',20)->nullable();
            $table->timestamps();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
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

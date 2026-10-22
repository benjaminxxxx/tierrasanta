<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plan_actividad_bonos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('registro_diario_id')->constrained('plan_registros_diarios')->cascadeOnDelete();
            $table->foreignId('actividad_id')->constrained('actividades')->cascadeOnDelete();
            $table->decimal('total_bono', 10, 2)->default(0.00);
            $table->boolean('bono_manual')->default(false);
            $table->timestamps();
            $table->foreignId('metodo_id')->nullable()->constrained('actividad_metodos')->nullOnDelete();

            $table->unique(['registro_diario_id', 'actividad_id'], 'registro_actividad_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plan_actividad_bonos');
    }
};

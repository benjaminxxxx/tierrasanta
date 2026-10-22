<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reg_registro_diario', function (Blueprint $table) {
            $table->id();
            $table->foreignId('consolidado_id')->nullable()->constrained('reg_resumen')->cascadeOnDelete();
            $table->string('campo');
            $table->time('hora_inicio');
            $table->time('hora_fin');
            $table->string('documento');
            $table->string('regador');
            $table->date('fecha');
            $table->boolean('sh')->default(false);
            $table->string('tipo_labor');
            $table->text('descripcion')->nullable();
            $table->boolean('por_acumulacion')->default(false);
            $table->boolean('referido_otro_dia')->default(false);
            $table->foreignId('campo_campania_id')->nullable()->constrained('campos_campanias')->nullOnDelete();
            $table->decimal('horas_ponderadas', 8, 2)->default(0.00);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reg_registro_diario');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('campania_informes_mensuales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campo_campania_id')->constrained('campos_campanias');
            $table->unsignedSmallInteger('anio');
            $table->unsignedTinyInteger('mes');
            $table->date('fecha_desde');
            $table->date('fecha_corte');
            $table->boolean('es_cierre')->default(false)->comment('La campaña termina este mes: corte en su fecha de fin');
            $table->unsignedInteger('version')->default(1);
            $table->longText('datos');
            $table->json('advertencias')->nullable();
            $table->decimal('total_soles', 14, 2)->default(0.00);
            $table->decimal('total_usd_ha', 14, 2)->nullable();
            $table->foreignId('generado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['anio', 'mes']);
            $table->unique(['campo_campania_id', 'anio', 'mes']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('campania_informes_mensuales');
    }
};

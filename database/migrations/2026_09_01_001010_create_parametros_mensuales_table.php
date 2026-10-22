<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('parametros_mensuales', function (Blueprint $table) {
            $table->id();
            $table->unsignedTinyInteger('mes');
            $table->unsignedSmallInteger('anio');
            $table->string('clave', 100);
            $table->decimal('valor', 15, 4)->nullable();
            $table->string('valor_texto', 500)->nullable();
            $table->boolean('valor_flag')->nullable();
            $table->text('observacion')->nullable();
            $table->foreignId('creado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('actualizado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['mes', 'anio', 'clave'], 'parametros_mes_anio_clave_unique');
            $table->index(['mes', 'anio'], 'parametros_mes_anio_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('parametros_mensuales');
    }
};

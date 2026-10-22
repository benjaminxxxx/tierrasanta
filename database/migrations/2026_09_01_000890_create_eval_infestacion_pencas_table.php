<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('eval_infestacion_pencas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campo_campania_id')->constrained('campos_campanias')->cascadeOnDelete();
            $table->unsignedTinyInteger('numero_penca');
            $table->unsignedSmallInteger('eval_primera_piso_2')->nullable();
            $table->unsignedSmallInteger('eval_primera_piso_3')->nullable();
            $table->unsignedSmallInteger('eval_segunda_piso_2')->nullable();
            $table->unsignedSmallInteger('eval_segunda_piso_3')->nullable();
            $table->unsignedSmallInteger('eval_tercera_piso_2')->nullable();
            $table->unsignedSmallInteger('eval_tercera_piso_3')->nullable();
            $table->timestamps();

            $table->unique(['campo_campania_id', 'numero_penca'], 'eval_infestacion_pencas_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('eval_infestacion_pencas');
    }
};

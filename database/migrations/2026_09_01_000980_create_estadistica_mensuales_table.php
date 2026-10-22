<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('estadistica_mensuales', function (Blueprint $table) {
            $table->id();
            $table->unsignedTinyInteger('mes');
            $table->unsignedSmallInteger('anio');
            $table->string('clave', 60);
            $table->decimal('valor', 12, 4);
            $table->decimal('valor_anterior', 12, 4)->nullable();
            $table->timestamps();

            $table->unique(['mes', 'anio', 'clave']);
            $table->index(['mes', 'anio']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('estadistica_mensuales');
    }
};

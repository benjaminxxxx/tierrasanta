<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rpt_distribucion_combustibles', function (Blueprint $table) {
            $table->id();
            $table->integer('mes');
            $table->integer('anio');
            $table->string('file_blanco')->nullable();
            $table->string('file_negro')->nullable();
            $table->decimal('total_combustible', 10, 2)->nullable();
            $table->decimal('total_costo', 10, 2)->nullable();
            $table->decimal('total_horas', 10, 2)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rpt_distribucion_combustibles');
    }
};

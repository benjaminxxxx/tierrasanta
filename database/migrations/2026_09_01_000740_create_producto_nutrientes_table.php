<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('producto_nutrientes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('producto_id')->constrained('productos')->cascadeOnDelete();
            $table->string('nutriente_codigo');
            $table->decimal('porcentaje', 5, 2);
            $table->timestamps();

            $table->foreign('nutriente_codigo')->references('codigo')->on('nutrientes')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('producto_nutrientes');
    }
};

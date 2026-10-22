<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('actividad_metodo_tramos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('metodo_id')->constrained('actividad_metodos')->cascadeOnDelete();
            $table->decimal('hasta', 10, 3)->nullable();
            $table->decimal('monto', 10, 4);
            $table->unsignedTinyInteger('orden')->default(1);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('actividad_metodo_tramos');
    }
};

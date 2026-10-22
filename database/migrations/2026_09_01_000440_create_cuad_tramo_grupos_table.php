<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cuad_tramo_grupos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cuad_tramo_laboral_id')->constrained('cuad_tramos_laborales')->cascadeOnDelete();
            $table->string('codigo_grupo');
            $table->unsignedInteger('orden');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cuad_tramo_grupos');
    }
};

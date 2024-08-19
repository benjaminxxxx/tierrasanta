<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('asignacion_familiar', function (Blueprint $table) {
            $table->id();
            $table->string('nombres');
            $table->date('fecha_nacimiento');
            $table->string('documento')->unique();
            $table->foreignId('empleado_id')->constrained()->onDelete('cascade');
            $table->boolean('esta_estudiando')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('asignacion_familiar');
    }
};

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
        Schema::create('campos', function (Blueprint $table) {
            
            $table->string('nombre')->primary();
            $table->string('campo_parent_nombre')->nullable();
            $table->string('grupo')->nullable();
            $table->integer('orden')->nullable();
            $table->string('estado')->nullable(); // ej: 'regando', 'sin regar'
            $table->string('etapa')->nullable();
            $table->float('area')->nullable(); // Área en metros cuadrados
            $table->decimal('pos_x',10,2)->nullable(); // Posición X en un canvas
            $table->decimal('pos_y',10,2)->nullable();
            $table->timestamps();
            $table->foreign('campo_parent_nombre')->references('nombre')->on('campos')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('campos');
    }
};

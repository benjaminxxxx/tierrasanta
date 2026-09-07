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
            $table->string('etapa')->nullable();
            $table->decimal('area', 10, 4)->nullable(); // Área en metros cuadrados
            $table->string('alias')->nullable();
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

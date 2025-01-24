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
        Schema::create('campos_campanias_consumos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('campos_campanias_id')->nullable();
            $table->unsignedBigInteger('categoria_id');
            $table->decimal('monto', 8, 2);
            $table->foreign('campos_campanias_id', 'fk_c_c_id')->references('id')->on('campos_campanias')->onDelete('cascade');
            $table->foreign('categoria_id')->references('id')->on('categoria_productos')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('campos_campanias_consumos');
    }
};

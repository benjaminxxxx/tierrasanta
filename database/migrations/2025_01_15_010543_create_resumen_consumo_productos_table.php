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
        Schema::create('resumen_consumo_productos', function (Blueprint $table) {
            $table->id();
            $table->date('fecha');
            $table->string('campo');
            $table->string('producto');
            $table->string('categoria');
            $table->decimal('cantidad', 10, 3)->nullable();
            $table->decimal('total_costo', 10, 2)->nullable();
            $table->unsignedBigInteger('campos_campanias_id')->nullable();
            $table->foreign('campos_campanias_id', 'fk_c_c2_id')->references('id')->on('campos_campanias')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('resumen_consumo_productos');
    }
};

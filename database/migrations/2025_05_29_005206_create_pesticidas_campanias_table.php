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
        Schema::create('pesticidas_campanias', function (Blueprint $table) {
            $table->id();
            $table->foreignId('producto_id')->constrained()->onDelete('cascade');
            $table->unsignedBigInteger('campo_campania_id');
            $table->date('fecha');
            $table->decimal('kg', 10, 2);
            $table->decimal('kg_ha', 10, 2);
            $table->timestamps();
            $table->foreign('campo_campania_id', 'fk_pesctcamp_campania')
                  ->references('id')
                  ->on('campos_campanias')
                  ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pesticidas_campanias');
    }
};

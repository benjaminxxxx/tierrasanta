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
        Schema::create('eval_brotes_por_pisos', function (Blueprint $table) {
            $table->id();
            
            $table->unsignedBigInteger('campania_id');
            $table->foreign('campania_id')
                ->references('id')
                ->on('campos_campanias')
                ->onDelete('restrict');

            $table->date('fecha')->nullable();
            $table->decimal('metros_cama_ha', 10, 3);
            $table->string('evaluador', 255);
            $table->timestamps();
            $table->unique('campania_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('eval_brotes_por_pisos');
    }
};

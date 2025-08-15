<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('campania_labores_costos', function (Blueprint $table) {
            $table->id();
            $table->integer('codigo_mano_obra');
            $table->string('codigo_labor', 50);
            $table->string('descripcion_labor', 255);
            $table->decimal('cantidad_ha', 10, 2);
            $table->decimal('costo_ha', 12, 2);
            $table->decimal('costo_total', 14, 2);
            $table->unsignedBigInteger('campo_campania_id');
            $table->foreign('campo_campania_id', 'fk_lcosto_campania')
                  ->references('id')
                  ->on('campos_campanias')
                  ->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('campania_labores_costos');
    }
};

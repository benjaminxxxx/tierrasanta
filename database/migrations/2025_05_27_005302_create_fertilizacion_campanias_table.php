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
        Schema::create('fertilizacion_campanias', function (Blueprint $table) {
            $table->id();
            $table->foreignId('producto_id')->constrained()->onDelete('cascade');
            $table->unsignedBigInteger('campo_campania_id');
            $table->date('fecha');
            $table->decimal('kg',5, 2);
            $table->decimal('kg_ha',5, 2);
            $table->decimal('n_ha',5, 2)->nullable();
            $table->decimal('p_ha',5, 2)->nullable();
            $table->decimal('k_ha',5, 2)->nullable();
            $table->decimal('ca_ha',5, 2)->nullable();
            $table->decimal('mg_ha',5, 2)->nullable();
            $table->decimal('zn_ha',5, 2)->nullable();
            $table->decimal('mn_ha',5, 2)->nullable();
            $table->decimal('fe_ha',5, 2)->nullable();
            $table->timestamps();
            $table->foreign('campo_campania_id', 'fk_fercamp_campania')
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
        Schema::dropIfExists('fertilizacion_campanias');
    }
};

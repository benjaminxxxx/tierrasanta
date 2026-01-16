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
        Schema::create('cochinilla_infestaciones', function (Blueprint $table) {
            $table->id();
            $table->enum('tipo_infestacion', ['infestacion', 'reinfestacion']);
            $table->date('fecha');
            $table->string('campo_nombre');
            $table->double('area')->nullable();
            $table->unsignedBigInteger('campo_campania_id')->nullable();
            $table->double('kg_madres');
            $table->double('kg_madres_por_ha')->nullable();
            $table->string('campo_origen_nombre');
            $table->enum('metodo', ['carton', 'tubo', 'malla']);
            $table->integer('numero_envases');
            $table->integer('capacidad_envase');
            $table->integer('infestadores')->nullable();
            $table->double('madres_por_infestador')->nullable();
            $table->double('infestadores_por_ha')->nullable();
            $table->timestamps();

            // Relaciones
            $table->foreign('campo_nombre')->references('nombre')->on('campos');
            $table->foreign('campo_origen_nombre')->references('nombre')->on('campos');
            $table->foreign('campo_campania_id')
                ->references('id')
                ->on('campos_campanias')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cochinilla_infestaciones');
    }
};

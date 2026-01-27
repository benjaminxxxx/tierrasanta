<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('reg_registro_diario', function (Blueprint $table) {
            $table->id();
            $table->string('campo');
            $table->time('hora_inicio');
            $table->time('hora_fin');
            //$table->time('total_horas');
            $table->string('documento');
            $table->string('regador');
            $table->date('fecha');
            $table->boolean('sh')->default(false);
            $table->string('tipo_labor');
            $table->text('descripcion')->nullable();
            $table->unsignedBigInteger('campo_campania_id')->nullable(); 
            $table->foreign('campo_campania_id')->references('id')->on('campos_campanias')->onDelete('set null');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('reg_registro_diario');
    }
};

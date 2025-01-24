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
        Schema::create('recogidas', function (Blueprint $table) {
            $table->id();
            
            $table->unsignedBigInteger('actividad_id');
            $table->integer('recogida_numero');
            $table->decimal('horas', 8, 2);
            $table->decimal('kg_estandar', 8, 2);
            $table->timestamps();
            $table->foreign('actividad_id', 'fk_actividad')
                ->references('id')->on('actividades')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('recogidas');
    }
};

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
        Schema::create('ins_usos', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');                        // 'Control de quereza', 'Control de arañita'
            $table->string('categoria_codigo')->nullable();  // 'pesticida', 'fertilizante', null = general
            $table->string('descripcion')->nullable();
            $table->unsignedBigInteger('creado_por')->nullable();
            $table->unsignedBigInteger('editado_por')->nullable();
            $table->foreign('creado_por')->references('id')->on('users')->nullOnDelete();
            $table->foreign('editado_por')->references('id')->on('users')->nullOnDelete();
            $table->unique(['nombre','categoria_codigo']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ins_usos');
    }
};

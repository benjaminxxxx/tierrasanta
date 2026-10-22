<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ins_categorias', function (Blueprint $table) {
            $table->string('codigo', 50);
            $table->string('descripcion');
            $table->text('definicion')->nullable();
            $table->text('criterio_uso')->nullable();
            $table->string('grupo_operativo', 50)->nullable();
            $table->timestamps();
            $table->primary('codigo');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ins_categorias');
    }
};

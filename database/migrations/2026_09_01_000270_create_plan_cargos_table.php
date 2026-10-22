<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plan_cargos', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->string('slug');
            $table->unsignedInteger('cupo_maximo')->nullable();
            $table->boolean('activo')->default(true);
            $table->string('eliminado_por')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique('slug');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plan_cargos');
    }
};

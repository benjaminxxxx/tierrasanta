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
        Schema::create('ins_subcategorias', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->string('categoria_codigo');
            $table->string('descripcion')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Auditoría
            $table->unsignedBigInteger('creado_por')->nullable();
            $table->unsignedBigInteger('editado_por')->nullable();
            $table->unsignedBigInteger('eliminado_por')->nullable();

            // FK
            $table->foreign('categoria_codigo')
                ->references('codigo')
                ->on('ins_categorias')
                ->restrictOnDelete();

            $table->foreign('creado_por')
                ->references('id')->on('users')
                ->nullOnDelete();

            $table->foreign('editado_por')
                ->references('id')->on('users')
                ->nullOnDelete();

            $table->foreign('eliminado_por')
                ->references('id')->on('users')
                ->nullOnDelete();

            // Unique: no puede haber dos subcategorías con el mismo nombre dentro de la misma categoría
            $table->unique(['categoria_codigo', 'nombre']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ins_subcategorias');
    }
};

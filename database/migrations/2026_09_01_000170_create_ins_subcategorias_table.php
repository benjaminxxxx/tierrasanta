<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ins_subcategorias', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->string('categoria_codigo');
            $table->string('descripcion')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->foreignId('creado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('editado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('eliminado_por')->nullable()->constrained('users')->nullOnDelete();

            $table->unique(['categoria_codigo', 'nombre']);

            $table->foreign('categoria_codigo')->references('codigo')->on('ins_categorias')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ins_subcategorias');
    }
};

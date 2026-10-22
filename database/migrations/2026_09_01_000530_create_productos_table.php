<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('productos', function (Blueprint $table) {
            $table->id();
            $table->string('nombre_comercial');
            $table->string('ingrediente_activo')->nullable();
            $table->string('codigo_tipo_existencia', 4)->nullable();
            $table->string('codigo_unidad_medida', 4)->nullable();
            $table->foreignId('subcategoria_id')->nullable()->constrained('ins_subcategorias')->nullOnDelete();
            $table->string('categoria_codigo', 50)->nullable();
            $table->foreignId('creado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('editado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('eliminado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('categoria_codigo')->references('codigo')->on('ins_categorias')->nullOnDelete();
            $table->foreign('codigo_tipo_existencia')->references('codigo')->on('sunat_tabla5_tipo_existencias')->nullOnDelete();
            $table->foreign('codigo_unidad_medida')->references('codigo')->on('sunat_tabla6_codigo_unidad_medida')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('productos');
    }
};

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
        Schema::create('productos', function (Blueprint $table) {
            $table->id();
            $table->string('nombre_comercial');
            $table->string('ingrediente_activo')->nullable();
            $table->string('categoria_pesticida')->nullable();
            $table->string('codigo_tipo_existencia', 4)->nullable();
            $table->string('codigo_unidad_medida', 4)->nullable();
            $table->unsignedBigInteger('subcategoria_id')
                ->nullable();

            // Establecer las claves foráneas
            $table->foreign('codigo_tipo_existencia')->references('codigo')->on('sunat_tabla5_tipo_existencias')->onDelete('set null');
            $table->foreign('codigo_unidad_medida')->references('codigo')->on('sunat_tabla6_codigo_unidad_medida')->onDelete('set null');
            /*$table->foreign('categoria_pesticida')
                ->references('codigo')
                ->on('categoria_pesticidas')
                ->nullOnDelete();*/

            $table->string('categoria_codigo', 50)->nullable();

            // 3. Agregar la clave foránea
            $table->foreign('categoria_codigo')
                ->references('codigo')
                ->on('ins_categorias')
                ->nullOnDelete();
            // -----------------------
            // AUDITORÍA
            // -----------------------
            $table->foreignId('creado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('editado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('eliminado_por')->nullable()->constrained('users')->nullOnDelete();

            $table->foreign('subcategoria_id')
                ->references('id')
                ->on('ins_subcategorias')
                ->nullOnDelete();

            // -----------------------
            // TIMESTAMPS + SOFT DELETE
            // -----------------------
            $table->timestamps();
            $table->softDeletes(); // deleted_at
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('productos');
    }
};

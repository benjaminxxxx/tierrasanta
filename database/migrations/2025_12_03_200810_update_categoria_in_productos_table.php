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
        Schema::table('productos', function (Blueprint $table) {
            $table->dropColumn('categoria');

            // 2. Crear el nuevo campo categoría (string) que referencia a ins_categorias
            $table->string('categoria_codigo', 50)->nullable();

            // 3. Agregar la clave foránea
            $table->foreign('categoria_codigo')
                ->references('codigo')
                ->on('ins_categorias')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('productos', function (Blueprint $table) {
            $table->dropForeign(['categoria_codigo']);
            $table->dropColumn('categoria_codigo');

            // Volver al enum original
            $table->enum('categoria', ['fertilizante', 'pesticida', 'combustible'])
                ->default('fertilizante');
        });
    }
};

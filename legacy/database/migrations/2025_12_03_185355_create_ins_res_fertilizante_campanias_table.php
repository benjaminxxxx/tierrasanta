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
        Schema::create('ins_res_fertilizante_campanias', function (Blueprint $table) {
            $table->id();

            // Relaciones
            $table->foreignId('producto_id')->nullable()
                ->constrained('productos')
                ->restrictOnDelete();

            $table->foreignId('campo_campania_id')->nullable()
                ->constrained('campos_campanias')
                ->cascadeOnDelete();

            // Datos
            $table->enum('etapa', ['infestacion', 'reinfestacion', 'cosecha'])->default('infestacion');
            $table->date('fecha')->nullable();
            $table->decimal('kg', 8, 2)->nullable();

            // Nutrientes
            $table->decimal('n_kg', 8, 2)->nullable();
            $table->decimal('p_kg', 8, 2)->nullable();
            $table->decimal('k_kg', 8, 2)->nullable();
            $table->decimal('ca_kg', 8, 2)->nullable();
            $table->decimal('mg_kg', 8, 2)->nullable();
            $table->decimal('zn_kg', 8, 2)->nullable();
            $table->decimal('mn_kg', 8, 2)->nullable();
            $table->decimal('fe_kg', 8, 2)->nullable();
            $table->decimal('corrector_salinidad_cant', 8, 2)->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ins_res_fertilizante_campanias');
    }
};

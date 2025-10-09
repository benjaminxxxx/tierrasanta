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
        Schema::create('labores', function (Blueprint $table) {
            $table->id();
            $table->string('nombre_labor');
            $table->string('codigo_mano_obra')->nullable();
            $table->integer('codigo')->unique()->nullable();
            $table->integer('estandar_produccion')->nullable();
            $table->string('unidades', 20)->nullable();
            $table->text('tramos_bonificacion')->nullable();
            $table->foreign('codigo_mano_obra')
                ->references('codigo')
                ->on('mano_obras')
                ->onDelete('set null');
            $table->foreignId('creado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('actualizado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('eliminado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('labores');
    }
};

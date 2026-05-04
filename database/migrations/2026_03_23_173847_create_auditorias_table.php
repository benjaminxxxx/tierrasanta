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
        Schema::create('auditorias', function (Blueprint $table) {
            $table->id();
            $table->string('modelo');               // App\Models\CochinillaInfestacion
            $table->string('modelo_id',10)->nullable();
            $table->enum('accion', ['crear', 'editar', 'eliminar']);
            $table->json('cambios')->nullable();    // {"antes": {...}, "despues": {...}}
            $table->string('observacion')->nullable();
            $table->unsignedBigInteger('usuario_id')->nullable();
            $table->string('usuario_nombre')->nullable(); // snapshot para no perder el nombre si se borra el user
            $table->timestamp('fecha_accion')->useCurrent();

            $table->index(['modelo', 'modelo_id']);
            $table->foreign('usuario_id')->references('id')->on('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('auditorias');
    }
};

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
        Schema::create('cuadrilleros', function (Blueprint $table) {
            $table->id();
            $table->string('nombres');
            $table->string('codigo_grupo')->nullable();
            $table->string('dni')->nullable()->unique();
            $table->boolean('estado')->default(true);
            $table->timestamps();

            $table->foreign('codigo_grupo')
                ->references('codigo')
                ->on('cua_grupos')
                ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cuadrilleros');
    }
};

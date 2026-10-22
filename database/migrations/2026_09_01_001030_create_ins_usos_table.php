<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ins_usos', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->string('categoria_codigo')->nullable();
            $table->string('descripcion')->nullable();
            $table->foreignId('creado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('editado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['nombre', 'categoria_codigo']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ins_usos');
    }
};

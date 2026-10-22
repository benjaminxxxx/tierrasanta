<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('maquinarias', function (Blueprint $table) {
            $table->id();
            $table->string('nombre')->comment('Nombre real, como Tractor Massey Grande');
            $table->string('alias_blanco')->nullable()->comment('Alias para kardex blanco, como Tractor MG1');
            $table->foreignId('combustible_producto_id')->nullable()->constrained('productos')->nullOnDelete();
            $table->boolean('usa_distribucion')->default(true);
            $table->string('consumo_modo', 10)->nullable();
            $table->decimal('consumo_estimado', 10, 3)->nullable();
            $table->string('placa', 20)->nullable();
            $table->string('foto')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('maquinarias');
    }
};

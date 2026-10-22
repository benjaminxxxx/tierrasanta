<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ins_kardex_reportes', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->unsignedSmallInteger('anio');
            $table->enum('estado', ['borrador', 'vigente', 'cerrado'])->default('borrador');
            $table->enum('tipo_kardex', ['blanco', 'negro'])->nullable();
            $table->json('grupos_operativos')->nullable();
            $table->string('file')->nullable();
            $table->timestamp('generado_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ins_kardex_reportes');
    }
};

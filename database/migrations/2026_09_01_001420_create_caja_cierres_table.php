<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('caja_cierres', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('anio');
            $table->unsignedTinyInteger('mes');
            $table->enum('estado', ['cerrado', 'abierto'])->default('cerrado');
            $table->decimal('saldo_final', 14, 2)->nullable();
            $table->unsignedInteger('movimientos')->default(0);
            $table->foreignId('cerrado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('cerrado_at')->nullable();
            $table->foreignId('reabierto_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reabierto_at')->nullable();
            $table->string('motivo_reapertura', 500)->nullable();
            $table->timestamps();

            $table->unique(['anio', 'mes']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('caja_cierres');
    }
};

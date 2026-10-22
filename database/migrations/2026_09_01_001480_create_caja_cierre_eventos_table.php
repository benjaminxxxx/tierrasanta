<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('caja_cierre_eventos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('caja_cierre_id')->constrained('caja_cierres')->cascadeOnDelete();
            $table->unsignedSmallInteger('anio');
            $table->unsignedTinyInteger('mes');
            $table->enum('accion', ['cerrado', 'reabierto']);
            $table->decimal('saldo', 14, 2)->nullable()->comment('Disponible al cierre del mes en ese momento');
            $table->unsignedInteger('movimientos')->default(0);
            $table->string('motivo', 500)->nullable();
            $table->foreignId('usuario_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('usuario_nombre', 150)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['anio', 'mes']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('caja_cierre_eventos');
    }
};

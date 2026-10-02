<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Historial de caja:
 * - Un movimiento eliminado guarda quién lo eliminó y por qué (sigue en la tabla, con soft delete).
 * - Cada cierre y reapertura de mes queda como un evento (caja_cierres solo tiene el estado actual).
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('caja_movimientos', function (Blueprint $table) {
            $table->foreignId('eliminado_por')->nullable()->after('actualizado_por')->constrained('users')->nullOnDelete();
            $table->string('motivo_eliminacion', 500)->nullable()->after('eliminado_por');
        });

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
        Schema::table('caja_movimientos', function (Blueprint $table) {
            $table->dropConstrainedForeignId('eliminado_por');
            $table->dropColumn('motivo_eliminacion');
        });
    }
};

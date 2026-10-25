<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Preferencias de cada usuario.
 *  - accesos_directos: rutas del menú que fijó en "Acceso directo", en orden (null = todavía no lo personalizó: ve los
 *    de fábrica, SistemaMenuReglas::ACCESOS_INICIALES).
 *  - menu_abrir_activo: al cargar una página, abrir el grupo del menú que la contiene.
 *  - menu_recordar_abiertos: dejar abiertos los grupos que el usuario dejó abiertos (se guardan en su navegador).
 *  Por defecto los dos en no: todos los grupos cargan cerrados.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('usuario_preferencias', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->json('accesos_directos')->nullable();
            $table->boolean('menu_abrir_activo')->default(false);
            $table->boolean('menu_recordar_abiertos')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('usuario_preferencias');
    }
};

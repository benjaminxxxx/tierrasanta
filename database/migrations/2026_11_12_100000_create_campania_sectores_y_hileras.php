<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;

/**
 * Hileras del campo y grupos de hileras por campaña con su etapa (mapa de etapas).
 *
 * Un campo tiene N hileras; en una campaña pueden ir desfasadas (unas se sembraron o infestaron antes que otras), así
 * que cada campaña tiene uno o más grupos de hileras (campania_sectores), cada uno con las fechas en que entró a cada
 * etapa (campania_sector_etapas). Al migrar se detectan con lo registrado (campania:detectar-sectores); luego se
 * corrigen a mano desde Campañas → Mapa de etapas. Por defecto 10 hileras por campo.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('campos', 'hileras')) {
            Schema::table('campos', function (Blueprint $table) {
                $table->unsignedSmallInteger('hileras')->default(10)->after('area')->comment('Número de hileras del campo (mapa de etapas)');
            });
        }

        Schema::create('campania_sectores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campo_campania_id')->constrained('campos_campanias')->cascadeOnDelete();
            $table->string('nombre', 40);
            $table->unsignedSmallInteger('hilera_desde');
            $table->unsignedSmallInteger('hilera_hasta');
            $table->string('origen', 12)->default('detectado')->comment('detectado: lo armó el sistema con las labores; manual: lo corrigió una persona');
            $table->timestamps();
        });

        Schema::create('campania_sector_etapas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campania_sector_id')->constrained('campania_sectores')->cascadeOnDelete();
            $table->string('etapa', 20)->comment('preparacion, siembra, infestacion, reinfestacion, cosecha_madres, cosecha');
            $table->date('fecha_desde')->comment('Desde cuándo el grupo de hileras está en esa etapa (hasta la siguiente)');
            $table->timestamps();

            $table->unique(['campania_sector_id', 'etapa', 'fecha_desde'], 'camp_sector_etapa_unica');
        });

        // Detección inicial con lo registrado (no frena la migración si falla: se puede volver a correr el comando)
        try {
            Artisan::call('campania:detectar-sectores');
            echo Artisan::output();
        } catch (\Throwable $e) {
            echo "  No se pudo detectar los grupos de hileras: {$e->getMessage()}. Corre: php artisan campania:detectar-sectores\n";
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('campania_sector_etapas');
        Schema::dropIfExists('campania_sectores');
        if (Schema::hasColumn('campos', 'hileras')) {
            Schema::table('campos', function (Blueprint $table) {
                $table->dropColumn('hileras');
            });
        }
    }
};

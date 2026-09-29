<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Periodo al que pertenece la tarea. El panel elige un mes (hoy) pero internamente trabaja
     * con un rango de fechas, así más adelante se puede filtrar por día sin cambiar el modelo.
     * Null = tarea sin periodo (se muestra siempre).
     */
    public function up(): void
    {
        Schema::table('tareas_pendientes', function (Blueprint $table) {
            $table->date('fecha_inicio')->nullable()->after('clave');
            $table->date('fecha_fin')->nullable()->after('fecha_inicio');
            $table->index(['estado', 'fecha_inicio', 'fecha_fin']);
        });
    }

    public function down(): void
    {
        Schema::table('tareas_pendientes', function (Blueprint $table) {
            $table->dropIndex(['estado', 'fecha_inicio', 'fecha_fin']);
            $table->dropColumn(['fecha_inicio', 'fecha_fin']);
        });
    }
};

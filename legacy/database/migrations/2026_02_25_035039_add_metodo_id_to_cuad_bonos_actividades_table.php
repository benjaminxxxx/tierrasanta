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
        Schema::table('cuad_bonos_actividades', function (Blueprint $table) {
            $table->foreignId('metodo_id')
                ->nullable()
                ->constrained('actividad_metodos')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cuad_bonos_actividades', function (Blueprint $table) {
            $table->dropForeign(['metodo_id']);
            $table->dropColumn('metodo_id');
        });
    }
};

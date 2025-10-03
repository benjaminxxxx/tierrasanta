<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('cuad_registros_diarios', function (Blueprint $table) {
            
            // Tramo donde se pagó el jornal
            $table->foreignId('tramo_pagado_jornal_id')
                ->nullable()
                ->constrained('cuad_tramo_laborals', 'id', 'fk_cuad_registros_diarios_jornal')
                ->onDelete('set null');

            // Tramo donde se pagó el bono
            $table->foreignId('tramo_pagado_bono_id')
                ->nullable()
                ->constrained('cuad_tramo_laborals', 'id', 'fk_cuad_registros_diarios_bono')
                ->onDelete('set null');

            
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cuad_registros_diarios', function (Blueprint $table) {
          

            $table->dropForeign('fk_cuad_registros_diarios_jornal');
            $table->dropColumn('tramo_pagado_jornal_id');

            $table->dropForeign('fk_cuad_registros_diarios_bono');
            $table->dropColumn('tramo_pagado_bono_id');
        });
    }
};

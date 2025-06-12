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
        Schema::table('gasto_adicional_por_grupo_cuadrillas', function (Blueprint $table) {
            $table->timestamp('fecha_gasto')->default(DB::raw('CURRENT_TIMESTAMP'))->after('updated_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('gasto_adicional_por_grupo_cuadrillas', function (Blueprint $table) {
            $table->dropColumn('fecha_gasto');
        });
    }
};

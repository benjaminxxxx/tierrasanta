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
        Schema::table('reporte_diario_riegos', function (Blueprint $table) {
            $table->unsignedBigInteger('campo_campania_id')->nullable()->after('id'); // o ajusta la posición con after('otro_campo')
            $table->foreign('campo_campania_id')->references('id')->on('campos_campanias')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('reporte_diario_riegos', function (Blueprint $table) {
            $table->dropForeign(['campo_campania_id']);
            $table->dropColumn('campo_campania_id');
        });
    }
};

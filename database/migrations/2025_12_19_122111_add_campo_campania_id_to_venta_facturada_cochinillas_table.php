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
        Schema::table('venta_facturada_cochinillas', function (Blueprint $table) {
            $table
                ->foreignId('campo_campania_id')
                ->nullable()
                ->after('id') // ajusta la posición si deseas
                ->constrained('campos_campanias')
                ->restrictOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('venta_facturada_cochinillas', function (Blueprint $table) {
            $table->dropForeign(['campo_campania_id']);
            $table->dropColumn('campo_campania_id');
        });
    }
};

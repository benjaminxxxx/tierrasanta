<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('cuad_registros_diarios', function (Blueprint $table) {
            $table->boolean('esta_pagado')->default(false); // ubícalo donde tenga más sentido
        });
    }

    public function down()
    {
        Schema::table('cuad_registros_diarios', function (Blueprint $table) {
            $table->dropColumn('esta_pagado');
        });
    }
};

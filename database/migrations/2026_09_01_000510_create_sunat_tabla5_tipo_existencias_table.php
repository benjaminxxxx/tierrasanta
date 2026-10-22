<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sunat_tabla5_tipo_existencias', function (Blueprint $table) {
            $table->string('codigo', 2);
            $table->string('descripcion');
            $table->primary('codigo');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sunat_tabla5_tipo_existencias');
    }
};

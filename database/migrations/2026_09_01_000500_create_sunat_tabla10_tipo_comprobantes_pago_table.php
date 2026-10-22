<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sunat_tabla10_tipo_comprobantes_pago', function (Blueprint $table) {
            $table->string('codigo', 4);
            $table->string('descripcion');
            $table->primary('codigo');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sunat_tabla10_tipo_comprobantes_pago');
    }
};

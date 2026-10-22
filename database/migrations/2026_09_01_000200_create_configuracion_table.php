<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('configuracion', function (Blueprint $table) {
            $table->string('codigo');
            $table->text('valor');
            $table->string('descripcion');
            $table->timestamps();
            $table->primary('codigo');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('configuracion');
    }
};

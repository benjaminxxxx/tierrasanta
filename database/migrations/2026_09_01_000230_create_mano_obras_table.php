<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mano_obras', function (Blueprint $table) {
            $table->string('codigo');
            $table->string('descripcion');
            $table->timestamps();
            $table->primary('codigo');

            $table->unique('descripcion');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mano_obras');
    }
};

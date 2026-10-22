<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('empresa', function (Blueprint $table) {
            $table->string('ruc', 11);
            $table->string('razon_social');
            $table->string('establecimiento');
            $table->primary('ruc');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('empresa');
    }
};

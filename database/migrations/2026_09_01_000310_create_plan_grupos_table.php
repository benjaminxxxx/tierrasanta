<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plan_grupos', function (Blueprint $table) {
            $table->string('codigo');
            $table->string('descripcion');
            $table->string('color');
            $table->timestamps();
            $table->primary('codigo');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plan_grupos');
    }
};

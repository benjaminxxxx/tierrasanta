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
        Schema::table('campos_campanias', function (Blueprint $table) {
            $table->date('eval_infest_fecha_primera')->nullable();
            $table->date('eval_infest_fecha_segunda')->nullable();
            $table->date('eval_infest_fecha_tercera')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('campos_campanias', function (Blueprint $table) {
            $table->dropColumn([
                'eval_infest_fecha_primera',
                'eval_infest_fecha_segunda',
                'eval_infest_fecha_tercera',
            ]);
        });
    }
};

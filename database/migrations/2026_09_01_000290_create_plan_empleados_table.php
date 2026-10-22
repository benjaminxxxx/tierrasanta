<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plan_empleados', function (Blueprint $table) {
            $table->id();
            $table->char('uuid', 36);
            $table->foreignId('persona_id')->nullable()->constrained('personas')->nullOnDelete();
            $table->string('nombres');
            $table->string('apellido_paterno')->nullable();
            $table->string('apellido_materno')->nullable();
            $table->string('documento');
            $table->date('fecha_ingreso')->nullable();
            $table->text('comentarios')->nullable();
            $table->string('email')->nullable();
            $table->string('numero')->nullable();
            $table->date('fecha_nacimiento')->nullable();
            $table->string('direccion')->nullable();
            $table->enum('genero', ['M', 'F'])->nullable();
            $table->integer('orden')->nullable();
            $table->foreignId('creado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('actualizado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('eliminado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->softDeletes();
            $table->timestamps();

            $table->unique('documento');
            $table->unique('email');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plan_empleados');
    }
};

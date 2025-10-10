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
        Schema::create('plan_empleados', function (Blueprint $table) {
            $table->id();
            $table->uuid();
            $table->string('nombres');
            $table->string('apellido_paterno')->nullable();
            $table->string('apellido_materno')->nullable();
            $table->string('documento')->unique(); // DNI u otro documento de identificación
            $table->date('fecha_ingreso')->nullable(); // Fecha de ingreso al trabajo
            $table->text('comentarios')->nullable(); // Comentarios adicionales sobre el empleado
            $table->string('email')->nullable()->unique(); // Correo electrónico del empleado
            $table->string('numero')->nullable(); // Número de teléfono
            $table->date('fecha_nacimiento')->nullable(); // Fecha de nacimiento del empleado
            $table->string('direccion')->nullable(); // Dirección del empleado
            $table->string('genero')->nullable();            
            $table->integer('orden')->nullable();
            $table->foreignId('creado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('actualizado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('eliminado_por')->nullable()->constrained('users')->nullOnDelete();
            
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('plan_empleados');
    }
};

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
        Schema::create('empleados', function (Blueprint $table) {
            $table->id();
            $table->char('code', 15);
            $table->string('nombres');
            $table->string('apellido_paterno')->nullable();
            $table->string('apellido_materno')->nullable();
            $table->string('documento')->unique(); // DNI u otro documento de identificación
            $table->date('fecha_ingreso')->nullable(); // Fecha de ingreso al trabajo
            $table->text('comentarios')->nullable(); // Comentarios adicionales sobre el empleado
            $table->string('status')->default('activo'); // Estado del empleado (activo, inactivo, etc.)
            $table->string('email')->nullable()->unique(); // Correo electrónico del empleado
            $table->string('numero')->nullable(); // Número de teléfono
            $table->decimal('salario', 8, 2)->nullable(); // Salario del empleado
            $table->date('fecha_nacimiento')->nullable(); // Fecha de nacimiento del empleado
            $table->string('direccion')->nullable(); // Dirección del empleado
            $table->string('genero')->nullable();
            $table->string('descuento_sp_id')->nullable();
            $table->foreign('descuento_sp_id')->references('codigo')->on('descuento_sp')->onDelete('set null');
            $table->string('cargo_id')->nullable(); // Relación con el cargo
            $table->foreign('cargo_id')->references('codigo')->on('cargos')->onDelete('set null'); // Clave foránea
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('empleados');
    }
};

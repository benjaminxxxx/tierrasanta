<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Carga de un macro de KARDEX anual (hoja INDICE + una hoja por código de existencia), kardex por kardex.
 *
 * Tablas propias e independientes: producto, kardex y usuario se guardan como datos (id + nombre) SIN claves
 * foráneas, para que el historial de la carga no cambie aunque después se eliminen o regeneren kardex.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('kardex_cargas', function (Blueprint $table) {
            $table->id();
            $table->string('archivo');                 // ruta en storage (disco local), se reemplaza al subir una versión corregida
            $table->string('nombre_original');
            $table->unsignedSmallInteger('anio');
            $table->string('tipo_kardex', 10);         // blanco | negro
            $table->unsignedInteger('version_archivo')->default(1);
            $table->unsignedBigInteger('subido_por')->nullable();
            $table->string('subido_por_nombre')->nullable();
            $table->timestamps();
        });

        Schema::create('kardex_carga_detalles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kardex_carga_id')->constrained('kardex_cargas')->cascadeOnDelete();
            $table->unsignedInteger('fila');           // fila de la hoja INDICE
            $table->string('codigo_existencia', 20);   // columna B = nombre de la hoja
            $table->string('nombre', 255);             // columna C
            $table->decimal('entradas_cantidad', 18, 4)->nullable();
            $table->decimal('entradas_importe', 24, 4)->nullable();
            $table->decimal('salidas_cantidad', 18, 4)->nullable();
            $table->decimal('salidas_importe', 24, 4)->nullable();
            // pendiente | exito | error | sin_producto | sin_movimientos
            $table->string('estado', 20)->default('pendiente');
            $table->text('mensaje')->nullable();
            $table->unsignedBigInteger('producto_id')->nullable();   // sin FK (historial independiente)
            $table->string('producto_nombre')->nullable();
            $table->unsignedBigInteger('kardex_id')->nullable();     // sin FK
            $table->boolean('kardex_creado')->default(false);
            $table->unsignedInteger('compras')->nullable();
            $table->unsignedInteger('salidas')->nullable();
            $table->unsignedInteger('intentos')->default(0);
            $table->unsignedInteger('version_archivo')->nullable();  // versión del macro con la que se procesó
            $table->timestamp('procesado_at')->nullable();
            $table->timestamps();

            $table->index(['kardex_carga_id', 'fila']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kardex_carga_detalles');
        Schema::dropIfExists('kardex_cargas');
    }
};

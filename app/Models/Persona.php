<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;

class Persona extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * Tabla asociada al modelo.
     *
     * @var string
     */
    protected $table = 'personas';

    /**
     * Atributos que se pueden asignar de manera masiva.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'codigo',
        'tipo',
        'tipo_documento',
        'numero_documento',
        'nombres',
        'apellido_paterno',
        'apellido_materno',
        'razon_social',
        'nombre_mostrar',
        'nombre_legal',
        'fecha_nacimiento',
        'genero',
        'estado_civil',
        'telefono_movil',
        'telefono',
        'email',
        'pais',
        'departamento',
        'provincia',
        'distrito',
        'codigo_postal',
        'direccion',
        'notas',
        'activo',
    ];

    /**
     * Conversión de tipos de atributos (Casting).
     *
     * @var array<string, string>
     */
    protected $casts = [
        'fecha_nacimiento' => 'date',
        'activo' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];
}
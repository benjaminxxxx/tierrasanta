<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TipoAsistencia extends Model
{
    use HasFactory;

    protected $table = 'plan_estados_asistencia';

    protected $primaryKey = 'codigo';   // 👈 Define el campo primario
    public $incrementing = false;       // 👈 Indica que no es autoincremental
    protected $keyType = 'string';      // 👈 Define que el tipo de clave es string

    protected $fillable = [
        'codigo',
        'descripcion',
        'horas_jornal',
        'color'
    ];
}

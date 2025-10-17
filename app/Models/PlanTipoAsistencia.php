<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PlanTipoAsistencia extends Model
{
    use HasFactory;

    protected $table = 'plan_tipo_asistencias';

    protected $primaryKey = 'codigo';   // 👈 Define el campo primario
    public $incrementing = false;       // 👈 Indica que no es autoincremental
    protected $keyType = 'string';      // 👈 Define que el tipo de clave es string

    protected $fillable = [
        'codigo',
        'descripcion',
        'horas_jornal',
        'color',
        'tipo',                   // ASISTENCIA, LICENCIA, VACACIONES, PERMISO
        'afecta_sueldo',          // bool
        'porcentaje_remunerado',  // decimal o entero
        'requiere_documento',     // bool
        'acumula_vacaciones',     // bool
        'acumula_asistencia',     // bool
        'activo',                 // bool
    ];
}

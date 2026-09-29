<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PlanTipoAsistencia extends Model
{
    use HasFactory;

    protected $table = 'plan_tipo_asistencias';

    /** Roles: le dan al código una función en el sistema (los tipos con rol no se pueden eliminar). */
    public const ROL_RENUNCIA = 'renuncia';
    public const ROLES = [
        self::ROL_RENUNCIA => 'Renuncia / cese (tareas de contrato sin finalizar)',
    ];

    /** Códigos de asistencia que tienen el rol dado. */
    public static function codigosConRol(string $rol): array
    {
        return static::where('rol', $rol)->pluck('codigo')->all();
    }

    protected $fillable = [
        'codigo',
        'descripcion',
        'horas_jornal',
        'color',
        'tipo',
        'afecta_sueldo',
        'porcentaje_remunerado',
        'requiere_documento',
        'acumula_vacaciones',
        'acumula_asistencia',
        'activo',
        'plan_tipo_suspension_id',
        'sin_suspension', // el código no genera suspensión (ej. feriado)
        'rol', // función en el sistema (ver ROLES); un tipo con rol no se puede eliminar
        'criterio_bono_asistencia'
    ];
    public function getAcumulaAsistenciaLabelAttribute()
    {
        return $this->acumula_asistencia ? 'SI' : 'NO';
    }
    public function tipoSuspension()
    {
        return $this->belongsTo(PlanTipoSuspension::class, 'plan_tipo_suspension_id');
    }
}

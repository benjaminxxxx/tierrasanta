<?php

namespace App\Models;

use App\Services\Planilla\Parametro\PlanillaParametroConsulta;
use App\Services\Planilla\Parametro\PlanillaParametroReglas;
use Illuminate\Database\Eloquent\Model;

/**
 * Mes de planilla. Guarda solo lo propio del mes (días laborables, horas, sueldo base, Excel generados).
 *
 * Las tasas del mes ($plan->rmv, ->essalud, ->vida_ley, ->gratificaciones, ->cts…, ver PlanillaParametroReglas::CATALOGO)
 * se leen de Parámetros de planilla, vigentes el día 1 del mes: no se copian. Quedan con candado cuando el mes tiene
 * planilla generada, así una planilla siempre se puede volver a leer igual.
 */
class PlanMensual extends Model
{
    protected $table = 'plan_mensuales';

    protected $fillable = [
        'mes',
        'anio',
        'total_horas',
        'dias_laborables',
        'remuneracion_basica',
        'total_empleados',
        'excel',
        'excel_oficina',
    ];

    /** Lista del registro diario (todos los empleados del mes, en el orden de "Gestionar lista") */
    public function detalle()
    {
        return $this->hasMany(PlanMensualDetalle::class, 'plan_mensual_id')->orderBy('orden');
    }

    /** Planilla calculada: las mismas filas, solo las calculadas y en el orden de la planilla */
    public function planilla()
    {
        return $this->hasMany(PlanMensualDetalle::class, 'plan_mensual_id')->whereNotNull('calculado_en')->orderBy('orden_planilla');
    }

    /** Parámetros del mes (codigo => valor o null) */
    public function parametros(): array
    {
        return $this->mes && $this->anio ? PlanillaParametroConsulta::delMes((int) $this->mes, (int) $this->anio) : [];
    }

    /** Descuento del sistema de pensiones del mes (HAB F, SNP…) */
    public function descuentoSp(?string $codigo): ?object
    {
        return PlanillaParametroConsulta::descuentoSp((int) $this->mes, (int) $this->anio, $codigo);
    }

    public function getAttribute($key)
    {
        if (is_string($key) && isset(PlanillaParametroReglas::CATALOGO[$key]) && !array_key_exists($key, $this->attributes)) {
            return $this->parametros()[$key] ?? null;
        }
        return parent::getAttribute($key);
    }
}

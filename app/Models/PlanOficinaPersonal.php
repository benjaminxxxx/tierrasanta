<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Planilla oficina (régimen general) de una persona en un mes: en planilla (5ta categoría) o por recibo por honorarios
 * (tipo_ingreso = honorarios). Ver App\Services\Planilla\Oficina\PlanillaOficinaProceso.
 */
class PlanOficinaPersonal extends Model
{
    protected $table = 'plan_oficina_personals';

    protected $guarded = ['id'];

    /** Columnas de montos (todas decimal:2) */
    public const MONTOS = [
        'remuneracion_basica', 'asignacion_familiar',
        'rem_sueldo', 'rem_vacaciones', 'rem_asignacion_familiar', 'total_remuneracion',
        'desc_afp_fondo', 'desc_afp_comision', 'desc_afp_prima', 'desc_snp', 'desc_renta_quinta', 'desc_renta_cuarta', 'total_descuentos', 'neto_planilla',
        'aporte_essalud', 'aporte_vida_ley',
        'provision_cts', 'provision_gratificacion', 'gratificacion', 'bonif_extraordinaria', 'cts', 'beneficios_pagados',
        'sueldo_real', 'bonificacion_negro', 'pago_blanco_mes', 'costo_contable', 'costo_total',
    ];

    protected function casts(): array
    {
        return array_fill_keys(self::MONTOS, 'decimal:2') + [
            'beneficios_mensuales' => 'boolean',
            'es_pensionista' => 'boolean',
            'ajustes' => 'array',
            'calculados' => 'array',
        ];
    }

    public function planMensual(): BelongsTo
    {
        return $this->belongsTo(PlanMensual::class);
    }

    public function planEmpleado(): BelongsTo
    {
        return $this->belongsTo(PlanEmpleado::class);
    }

    public function esHonorarios(): bool
    {
        return $this->tipo_ingreso === 'honorarios';
    }

    /** @return array{monto: float, motivo: ?string}|null */
    public function ajuste(string $columna): ?array
    {
        return $this->ajustes[$columna] ?? null;
    }

    public function calculado(string $columna): ?float
    {
        $v = $this->calculados[$columna] ?? null;
        return $v === null ? null : (float) $v;
    }
}

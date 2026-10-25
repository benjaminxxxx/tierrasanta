<?php

namespace App\Models;

/** Partícula del contrato: sueldo, remuneración básica (opcional), bonificación, compensación vacacional, modalidad. */
class PlanContratoRemuneracion extends PlanParticula
{
    protected $table = 'plan_contrato_remuneraciones';

    protected $fillable = [
        'plan_contrato_id', 'desde', 'sueldo', 'remuneracion_basica', 'bonificacion', 'compensacion_vacacional',
        'modalidad_pago', 'beneficios_mensuales', 'motivo', 'creado_por', 'actualizado_por',
    ];

    protected $casts = [
        'desde' => 'date',
        'sueldo' => 'decimal:2',
        'remuneracion_basica' => 'decimal:2',
        'bonificacion' => 'decimal:2',
        'compensacion_vacacional' => 'decimal:2',
        'beneficios_mensuales' => 'boolean',
    ];

    public function contrato()
    {
        return $this->belongsTo(PlanContrato::class, 'plan_contrato_id');
    }
}

<?php

namespace App\Services;

use App\Models\PlanDescuentoSp;
use App\Models\PlanMensual;
use App\Models\PlanMensualDetalle;
use App\Models\PlanMensualSpDesc;
use App\Models\PlanPrimaComisionHistorico;
use App\Services\Configuracion\ConfiguracionHistorialServicio;
use App\Services\Excel\Planilla\ExcelPlanillaMensual;
use Illuminate\Support\Carbon;
use App\Models\ParametroMensual;

class PlanillaMensualServicio
{
    public static function obtenerSueldoBase(
        int $mes,
        int $anio
    ): float {
        $rmv = ConfiguracionHistorialServicio::valorVigente(
            'rmv',
            $mes,
            $anio
        );

        $diasTotalMes = Carbon::create($anio, $mes, 1)->daysInMonth;

        return round(($rmv / 30) * $diasTotalMes, 2);
    }
    public static function guardarConfiguracionDesdeParametros($mes, $anio): PlanMensual
    {
        $codigosObligatorios = [
            'rmv',
            'beta30',
            'gratificaciones',
            'essalud_gratificaciones',
            'essalud',
            'vida_ley',
            'pension_sctr',
            'essalud_eps',
            'asignacion_familiar',
            'cts'
        ];

        $config = ConfiguracionHistorialServicio::obtenerValoresVigentes($codigosObligatorios, $mes, $anio);

        // rem_basica_essalud no es un parámetro configurado, se deriva de la RMV vigente.
        // Ajusta esta regla a tu fórmula real si es distinta.
        $remBasicaEssalud = isset($config['rmv'])
            ? round($config['rmv'], 2)
            : null;

        return PlanMensual::updateOrCreate(
            ['mes' => $mes, 'anio' => $anio],
            [
                'rmv' => $config['rmv'] ?? null,
                'beta30' => $config['beta30'] ?? null,
                'gratificaciones' => $config['gratificaciones'] ?? null,
                'essalud_gratificaciones' => $config['essalud_gratificaciones'] ?? null,
                'essalud' => $config['essalud'] ?? null,
                'vida_ley' => $config['vida_ley'] ?? null,
                'pension_sctr' => $config['pension_sctr'] ?? null,
                'essalud_eps' => $config['essalud_eps'] ?? null,
                'asignacion_familiar' => $config['asignacion_familiar'] ?? null,
                'rem_basica_essalud' => $remBasicaEssalud,
                'cts' => $config['cts'] ?? null,
            ]
        )->fresh();
    }
    public function generarExcel($params)
    {
        return app(ExcelPlanillaMensual::class)->generarPlanillaMensual($params);
    }
    public function guardarOrdenMensualEmpleados($mes, $anio, $listaPlanilla)
    {

        // Buscar o crear el plan mensual
        $planMensual = PlanMensual::firstOrCreate(
            ['mes' => $mes, 'anio' => $anio]
        );

        $planMensualId = $planMensual->id;

        // Obtener los IDs de empleados en la nueva lista
        $nuevosIds = collect($listaPlanilla)->pluck('id')->filter()->unique()->toArray();

        // Obtener los detalles actuales del plan
        $detallesActuales = PlanMensualDetalle::where('plan_mensual_id', $planMensualId)->get();

        // Eliminar solo los detalles cuyos empleados ya no están en la nueva lista
        $detallesAEliminar = $detallesActuales->whereNotIn('plan_empleado_id', $nuevosIds);

        if ($detallesAEliminar->isNotEmpty()) {

            // Cargar relaciones para evitar n+1
            $detallesAEliminar->load('registrosDiarios');

            foreach ($detallesAEliminar as $detalle) {

                if ($detalle->registrosDiarios->isNotEmpty()) {

                    // Tomar la primera fecha con registros diarios
                    $fecha = $detalle->registrosDiarios->first()->fecha;

                    throw new \Exception(
                        "El empleado {$detalle->nombres} tiene registros diarios en la fecha {$fecha}. " .
                        "No se puede eliminar porque ya no está en la planilla."
                    );
                }
            }

            // Si llegamos aquí, NINGÚN detalle tiene registros → se puede eliminar
            PlanMensualDetalle::whereIn('id', $detallesAEliminar->pluck('id'))->delete();
        }

        // Actualizar o crear los detalles de los empleados actuales
        foreach ($listaPlanilla as $indiceOrden => $empleado) {

            PlanMensualDetalle::updateOrCreate(
                [
                    'plan_empleado_id' => $empleado['id'],
                    'plan_mensual_id' => $planMensualId,
                ],
                [
                    'nombres' => $empleado['nombres'] ?? null,
                    'documento' => $empleado['documento'] ?? null,
                    //'grupo' => $empleado['grupo'] ?? null,
                    'orden' => $empleado['orden'],
                    //'spp_snp' => $empleado['spp_snp'],
                ]
            );
        }
    }

    public function obtenerPlanillaXFecha($fecha)
    {
        $carbon = Carbon::parse($fecha);
        return $this->obtenerPlanillaXMesAnio($carbon->month, $carbon->year);
    }
    //class PlanillaMensualServicio
    public function obtenerPlanillaXMesAnio($mes, $anio, $orden = 'orden')
    {
        return PlanMensualDetalle::whereHas('planillaMensual', function ($q) use ($mes, $anio) {
            $q->where('mes', $mes)
                ->where('anio', $anio);
        })
            ->with([
                'registrosDiarios',
                'empleado.contratos' => function ($q) use ($mes, $anio) {
                    $inicioMes = Carbon::createFromDate($anio, $mes, 1)->startOfMonth();
                    $finMes = Carbon::createFromDate($anio, $mes, 1)->endOfMonth();
                    $q->where('fecha_inicio', '<=', $finMes)
                        ->where(function ($q2) use ($inicioMes) {
                            $q2->whereNull('fecha_fin')->orWhere('fecha_fin', '>=', $inicioMes);
                        })
                        ->orderByDesc('fecha_inicio')
                        ->limit(1);
                },
                'empleado.sueldos' => function ($q) use ($mes, $anio) {
                    $inicioMes = Carbon::createFromDate($anio, $mes, 1)->startOfMonth();
                    $finMes = Carbon::createFromDate($anio, $mes, 1)->endOfMonth();
                    $q->where('fecha_inicio', '<=', $finMes)
                        ->where(function ($q2) use ($inicioMes) {
                            $q2->whereNull('fecha_fin')->orWhere('fecha_fin', '>=', $inicioMes);
                        })
                        ->orderByDesc('fecha_inicio')
                        ->limit(1);
                }
            ])
            ->orderBy($orden)
            ->get();
    }
    /**
     * Solo lee y arma el array. NO toca la base de datos.
     */
    public static function obtenerConfiguracionDesdeParametros($mes, $anio): array
    {
        $codigosObligatorios = [
            'rmv',
            'beta30',
            'gratificaciones',
            'essalud_gratificaciones',
            'essalud',
            'vida_ley',
            'pension_sctr',
            'essalud_eps',
            'asignacion_familiar',
            'cts'
        ];

        $config = ConfiguracionHistorialServicio::obtenerValoresVigentes($codigosObligatorios, $mes, $anio);

        $config['rem_basica_essalud'] = isset($config['rmv'])
            ? round($config['rmv'], 2)
            : null;

        return $config;
    }

    /**
     * Persiste el array ya obtenido dentro de plan_mensuales.
     * Se llama SOLO al generar, nunca al previsualizar.
     */
    public static function guardarConfiguracionEnPlanMensual(int $planMensualId, array $config): PlanMensual
    {
        $planMensual = PlanMensual::findOrFail($planMensualId);

        $planMensual->update([
            'rmv' => $config['rmv'] ?? null,
            'beta30' => $config['beta30'] ?? null,
            'gratificaciones' => $config['gratificaciones'] ?? null,
            'essalud_gratificaciones' => $config['essalud_gratificaciones'] ?? null,
            'essalud' => $config['essalud'] ?? null,
            'vida_ley' => $config['vida_ley'] ?? null,
            'pension_sctr' => $config['pension_sctr'] ?? null,
            'essalud_eps' => $config['essalud_eps'] ?? null,
            'asignacion_familiar' => $config['asignacion_familiar'] ?? null,
            'rem_basica_essalud' => $config['rem_basica_essalud'] ?? null,
            'cts' => $config['cts'] ?? null,
        ]);

        return $planMensual->fresh();
    }

    /**
     * Obtiene el valor vigente por AFP: el registro más reciente cuya
     * fecha_inicio sea <= al periodo consultado, uno por cada referencia.
     * Si no hay cambios desde enero, en julio sigue trayendo el de enero.
     * NO persiste. Usado para vista previa en el modal.
     */
    public static function obtenerDescuentosVigentes($mes, $anio)
    {
        $fecha = Carbon::createFromDate($anio, $mes, 1)->format('Y-m-d');

        $sub = PlanPrimaComisionHistorico::query()
            ->selectRaw('*, ROW_NUMBER() OVER (PARTITION BY referencia ORDER BY fecha_inicio DESC) as rn')
            ->where('fecha_inicio', '<=', $fecha);

        return PlanPrimaComisionHistorico::query()
            ->fromSub($sub, 'pc')
            ->where('rn', 1)
            ->orderByRaw("FIELD(referencia, 'HABITAT', 'INTEGRA', 'PRIMA', 'PROFUTURO')")
            ->get();
    }

    /**
     * Snapshot real (persistido) por código de modalidad (HAB F, HAB M, ..., SNP).
     * Se llama SOLO al generar, dentro de la misma transacción que la proyección.
     */
    public static function snapshotDescuentosSp(int $planMensualId, $mes, $anio): void
    {
        $primasComisiones = self::obtenerDescuentosVigentes($mes, $anio)->keyBy('referencia');

        if ($primasComisiones->isEmpty()) {
            throw new \Exception("No hay comisiones/primas AFP configuradas para {$mes}/{$anio}.");
        }

        $catalogo = PlanDescuentoSp::all();

        foreach ($catalogo as $descuento) {
            if ($descuento->codigo === 'SNP') {
                $descuentoSnp = ConfiguracionHistorialServicio::valorVigente('descuento_snp', $mes, $anio);

                PlanMensualSpDesc::updateOrCreate(
                    ['plan_mensual_id' => $planMensualId, 'codigo' => 'SNP'],
                    [
                        'referencia' => 'SNP',
                        'tipo' => null,
                        'comision' => 0,
                        'prima_seguros' => 0,
                        'aporte_obligatorio' => $descuentoSnp,
                        'porcentaje' => $descuentoSnp,
                        'porcentaje_65' => $descuentoSnp,
                    ]
                );
                continue;
            }

            $pc = $primasComisiones->get($descuento->referencia);

            if (!$pc) {
                throw new \Exception("Falta configurar comisiones/primas para {$descuento->referencia} en {$mes}/{$anio}.");
            }

            if ($descuento->tipo === 'Flujo') {
                $comision = $pc->comision_flujo;
                $porcentaje = $pc->comision_flujo + $pc->prima_seguros + $pc->aporte_obligatorio;
                $porcentaje_65 = $pc->comision_flujo + $pc->aporte_obligatorio;
            } else { // Mixta
                $comision = 0;
                $porcentaje = $pc->prima_seguros + $pc->aporte_obligatorio;
                $porcentaje_65 = $pc->aporte_obligatorio;
            }

            PlanMensualSpDesc::updateOrCreate(
                ['plan_mensual_id' => $planMensualId, 'codigo' => $descuento->codigo],
                [
                    'referencia' => $descuento->referencia,
                    'tipo' => $descuento->tipo,
                    'comision' => $comision,
                    'prima_seguros' => $pc->prima_seguros,
                    'aporte_obligatorio' => $pc->aporte_obligatorio,
                    'porcentaje' => $porcentaje,
                    'porcentaje_65' => $porcentaje_65,
                ]
            );
        }
    }
}
<?php

namespace App\Services;

use App\Models\ParametroMensual;
use App\Models\PlanResumenDiario;

class AsistenciasResumenServicio
{
    // ── Constantes de claves ──────────────────────────────────────────
    const PREFIX       = 'asistencias_resumen_';
    const CLAVE_TOTAL  = 'asistencias_total_planilla';
    const CLAVE_DIAS   = 'asistencias_dias_habiles';
    const CLAVE_EMPL   = 'asistencias_empleados';

    // ─────────────────────────────────────────────────────────────────
    // Recalcula un mes y lo persiste en parametros_mensuales
    // ─────────────────────────────────────────────────────────────────
    public function recalcularMes(int $mes, int $anio): void
    {
        $resumenes = PlanResumenDiario::with('totales')
            ->whereYear('fecha', $anio)
            ->whereMonth('fecha', $mes)
            ->where('total_planilla', '>', 0)
            ->get();

        $this->limpiarMes($mes, $anio);

        if ($resumenes->isEmpty()) {
            return;
        }

        $diasHabiles = $resumenes->count();
        $empleados   = (int) round($resumenes->avg('total_planilla'));
        $totalBase   = $diasHabiles * $empleados;

        $agregado = [];
        foreach ($resumenes as $resumen) {
            foreach ($resumen->totales as $t) {
                $cod = $t->codigo;
                if (!isset($agregado[$cod])) {
                    $agregado[$cod] = [
                        'codigo'        => $cod,
                        'descripcion'   => $t->descripcion,
                        'color'         => $t->color,
                        'tipo'          => $t->tipo,
                        'acumula'       => $t->acumula_asistencia,
                        'afecta_sueldo' => $t->afecta_sueldo,
                        'total'         => 0,
                    ];
                }
                $agregado[$cod]['total'] += $t->total_asistidos;
            }
        }

        ParametroMensual::establecer($mes, $anio, self::CLAVE_TOTAL,
            valor: $totalBase,
            observacion: "Base: {$diasHabiles} días × {$empleados} empleados");

        ParametroMensual::establecer($mes, $anio, self::CLAVE_DIAS,
            valor: $diasHabiles);

        ParametroMensual::establecer($mes, $anio, self::CLAVE_EMPL,
            valor: $empleados);

        foreach ($agregado as $cod => $datos) {
            ParametroMensual::updateOrCreate(
                ['mes' => $mes, 'anio' => $anio, 'clave' => self::PREFIX . $cod],
                ['valor_texto' => json_encode($datos, JSON_UNESCAPED_UNICODE),
                 'observacion' => 'Resumen mensual de asistencias']
            );
        }
    }

    // ─────────────────────────────────────────────────────────────────
    // Elimina todos los parámetros de asistencias de un mes
    // ─────────────────────────────────────────────────────────────────
    public function limpiarMes(int $mes, int $anio): void
    {
        ParametroMensual::where('mes', $mes)
            ->where('anio', $anio)
            ->where(function ($q) {
                $q->where('clave', self::CLAVE_TOTAL)
                  ->orWhere('clave', self::CLAVE_DIAS)
                  ->orWhere('clave', self::CLAVE_EMPL)
                  ->orWhere('clave', 'like', self::PREFIX . '%');
            })
            ->delete();
    }

    // ─────────────────────────────────────────────────────────────────
    // Lee los datos ya persistidos de un mes
    // Devuelve null si no hay datos
    // ─────────────────────────────────────────────────────────────────
    public function cargarMes(int $mes, int $anio): ?array
    {
        $totalBase   = (int) ParametroMensual::obtener(self::CLAVE_TOTAL, $mes, $anio, 0);
        $diasHabiles = (int) ParametroMensual::obtener(self::CLAVE_DIAS,  $mes, $anio, 0);
        $empleados   = (int) ParametroMensual::obtener(self::CLAVE_EMPL,  $mes, $anio, 0);

        $params = ParametroMensual::where('mes', $mes)
            ->where('anio', $anio)
            ->where('clave', 'like', self::PREFIX . '%')
            ->get();

        if ($params->isEmpty() || $totalBase === 0) {
            return null;
        }

        $totales = $params
            ->map(function ($p) use ($totalBase) {
                $datos = json_decode($p->valor_texto, true);
                if (!$datos) return null;
                return array_merge($datos, [
                    'porcentaje' => round(($datos['total'] / $totalBase) * 100, 1),
                ]);
            })
            ->filter()
            ->sortByDesc('total')
            ->values()
            ->toArray();

        return compact('totalBase', 'diasHabiles', 'empleados', 'totales');
    }

    // ─────────────────────────────────────────────────────────────────
    // Recalcula si no hay datos; devuelve cargarMes()
    // ─────────────────────────────────────────────────────────────────
    public function cargarORecalcularMes(int $mes, int $anio): ?array
    {
        $datos = $this->cargarMes($mes, $anio);
        if ($datos !== null) return $datos;

        $this->recalcularMes($mes, $anio);
        return $this->cargarMes($mes, $anio);
    }
}
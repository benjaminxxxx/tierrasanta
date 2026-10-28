<?php

namespace App\Services\Riego\Cuadrilla;

use App\Models\ConsolidadoRiego;
use App\Models\CuadDetalleHora;
use App\Models\CuadRegistroDiario;
use App\Models\Cuadrillero;
use App\Models\PlanContrato;
use App\Models\ReporteDiarioRiego;
use App\Services\Cuadrilla\Persona\CuadrillaPersonaConsulta;
use Illuminate\Support\Carbon;

/**
 * Coherencia del riego hecho por cuadrilleros entre las tres fuentes (solo lectura):
 *   1. Reporte de riego (horas regadas por campo y jornal de riego del día).
 *   2. Reporte semanal / registro de cuadrilla: horas pagadas del día (total_horas).
 *   3. Detalle del registro de cuadrilla: el riego va como FDM / 81 en el horario del riego.
 *
 * Problemas por regador y día:
 *   - es_planilla: el regador es trabajador de planilla con contrato ese día, pero el riego está a nombre de un cuadrillero.
 *   - sin_registro: regó pero no tiene registro de cuadrilla ese día (no se le paga).
 *   - labor: en el horario del riego hay tramos que no son FDM/81 (se costearían dos veces).
 *   - horas: las horas FDM/81 del registro de cuadrilla no son el jornal de riego.
 *   - costo: lo que la BDD costea ese día no es lo que se le paga (informativo: incluye el banco de horas).
 */
class RiegoCuadrillaConsulta
{
    public function __construct(private CuadrillaPersonaConsulta $personas)
    {
    }

    /** @return array<int, array> una fila por regador y día con problemas (y sincronizado = true/false de todos) */
    public function revisar(string $inicio, string $fin): array
    {
        $consolidados = ConsolidadoRiego::where('trabajador_type', Cuadrillero::class)
            ->whereBetween('fecha', [$inicio, $fin])->orderBy('fecha')->get();
        return $consolidados->map(fn($c) => $this->revisarConsolidado($c))->all();
    }

    /**
     * @return array{consolidado_id:int, cuadrillero_id:int, nombre:string, fecha:string, problemas:array<string,string>,
     *               sincronizado:bool, riego:?string, horas_riego:float, horas_81:?float, horas_pagadas:?float, horas_bdd:?float}
     */
    public function revisarConsolidado(ConsolidadoRiego $c): array
    {
        $fecha = Carbon::parse($c->fecha)->toDateString();
        $cuadrillero = Cuadrillero::withTrashed()->find($c->trabajador_id);
        $nombre = $cuadrillero?->nombres ?? $c->trabajador_nombre ?? "Cuadrillero {$c->trabajador_id}";
        $jornal = (int) $c->minutos_jornal;
        $reporte = ReporteDiarioRiego::where('consolidado_id', $c->id)->get();
        [$rIni, $rFin] = $this->ventana($c, $reporte);
        $problemas = [];

        // 1) ¿Es de planilla? (el riego debió registrarse a su nombre de planilla)
        $empleado = $cuadrillero ? $this->personas->empleadoConContrato($cuadrillero, $fecha) : null;
        if ($empleado) {
            $problemas['es_planilla'] = "Parece ser {$empleado['nombre']}, de planilla. Si es trabajo extra que se paga como cuadrilla (domingo, horas de más), está bien, "
                . 'pero regístralo en la fecha real en que se trabajó; si es su jornada de planilla, regístralo como planilla.';
        }

        $registros = $cuadrillero ? CuadRegistroDiario::where('cuadrillero_id', $cuadrillero->id)->whereDate('fecha', $fecha)->get() : collect();
        $horas81 = $pagado = $bdd = null;
        if ($registros->isEmpty()) {
            if ($jornal > 0 && !isset($problemas['es_planilla'])) {
                $problemas['sin_registro'] = 'Regó ' . $this->h($jornal) . ' h pero no tiene registro de cuadrilla ese día: no se le paga. Agrégalo a su grupo con FDM/81.';
            }
        } else {
            $tramos = CuadDetalleHora::whereIn('registro_diario_id', $registros->pluck('id'))->get();

            $malos = RiegoCuadrillaReglas::tramosFueraDeRegla($tramos, $rIni, $rFin);
            if ($malos) {
                $problemas['labor'] = 'En el horario del riego (' . ($rIni ?? '?') . '-' . ($rFin ?? '?') . ') hay tramos que no son FDM/81: '
                    . implode(', ', $malos) . '. El riego de un cuadrillero se registra como FDM/81.';
            }

            $min81 = $tramos->filter(fn($t) => RiegoCuadrillaReglas::esTramoRiego($t->campo_nombre, $t->codigo_labor))
                ->sum(fn($t) => RiegoCuadrillaReglas::minutos($t->hora_inicio, $t->hora_fin));
            $horas81 = round($min81 / 60, 2);
            if (abs($min81 - $jornal) > RiegoCuadrillaReglas::TOLERANCIA_MIN) {
                $problemas['horas'] = 'El registro de cuadrilla tiene ' . $this->h($min81) . ' h en FDM/81 y el jornal de riego es ' . $this->h($jornal) . ' h.';
            }

            // Costo: lo pagado (horas no a destajo) frente a lo que la BDD costea (reporte de riego + tramos fuera del horario del riego)
            $minPagado = (int) round($registros->sum(fn($r) => max(0, (float) $r->total_horas - (float) $r->horas_destajo)) * 60);
            $minReporte = (int) $reporte->reject(fn($r) => mb_strtoupper((string) $r->campo) === RiegoCuadrillaReglas::CAMPO && $r->por_acumulacion)
                ->sum(fn($r) => $r->por_acumulacion ? RiegoCuadrillaReglas::minutos($r->hora_inicio, $r->hora_fin) : (int) round(((float) $r->horas_ponderadas) * 60));
            $minFuera = $tramos->filter(fn($t) => !RiegoCuadrillaReglas::esTramoRiego($t->campo_nombre, $t->codigo_labor)
                    && RiegoCuadrillaReglas::minutosCruce($t->hora_inicio, $t->hora_fin, $rIni, $rFin) === 0)
                ->sum(fn($t) => RiegoCuadrillaReglas::minutos($t->hora_inicio, $t->hora_fin));
            $pagado = round($minPagado / 60, 2);
            $bdd = round(($minReporte + $minFuera) / 60, 2);
            if (abs($minReporte + $minFuera - $minPagado) > RiegoCuadrillaReglas::TOLERANCIA_MIN) {
                $problemas['costo'] = 'Se le pagan ' . $this->h($minPagado) . ' h y la BDD costea ' . $this->h($minReporte + $minFuera) . ' h'
                    . ($minReporte > $jornal ? ' (regó más que su jornal: banco de horas)' : '') . '.';
            }
        }

        return [
            'consolidado_id' => $c->id,
            'cuadrillero_id' => (int) $c->trabajador_id,
            'nombre' => $nombre,
            'fecha' => $fecha,
            'problemas' => $problemas,
            // "costo" es informativo (banco de horas): no desincroniza
            // "costo" (banco de horas) y "es_planilla" (trabajo extra pagado como cuadrilla) son informativos: no desincronizan
            'sincronizado' => !array_diff_key($problemas, ['costo' => true, 'es_planilla' => true]),
            'riego' => $rIni && $rFin ? "{$rIni}-{$rFin}" : null,
            'horas_riego' => round($jornal / 60, 2),
            'horas_81' => $horas81,
            'horas_pagadas' => $pagado,
            'horas_bdd' => $bdd,
        ];
    }

    /**
     * Al guardar tramos de cuadrilla: si el cuadrillero regó ese día, lo que cae en el horario del riego debe ser FDM/81.
     * Devuelve el mensaje de error o null.
     */
    public function validarTramos(?int $cuadrilleroId, string $fecha, array $tramos): ?string
    {
        if (!$cuadrilleroId || !$tramos) {
            return null;
        }
        $c = ConsolidadoRiego::where('trabajador_type', Cuadrillero::class)->where('trabajador_id', $cuadrilleroId)
            ->whereDate('fecha', $fecha)->where('minutos_jornal', '>', 0)->first();
        if (!$c) {
            return null;
        }
        [$ini, $fin] = $this->ventana($c);
        $malos = RiegoCuadrillaReglas::tramosFueraDeRegla($tramos, $ini, $fin);
        return $malos
            ? "regó de {$ini} a {$fin} (reporte de riego): en ese horario su labor debe ser FDM/81, no " . implode(', ', $malos) . '.'
            : null;
    }

    /** Horario del riego del día: el del consolidado o, si no tiene, de la primera a la última fila del reporte. */
    public function ventana(ConsolidadoRiego $c, $reporte = null): array
    {
        $ini = RiegoCuadrillaReglas::hora($c->hora_inicio);
        $fin = RiegoCuadrillaReglas::hora($c->hora_fin);
        if (!$ini || !$fin) {
            $reporte ??= ReporteDiarioRiego::where('consolidado_id', $c->id)->get();
            $ini ??= $reporte->min(fn($r) => RiegoCuadrillaReglas::hora($r->hora_inicio));
            $fin ??= $reporte->max(fn($r) => RiegoCuadrillaReglas::hora($r->hora_fin));
        }
        return [$ini, $fin];
    }

    private function h(float $minutos): string
    {
        return number_format($minutos / 60, 2);
    }
}

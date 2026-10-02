<?php

namespace App\Services\Planilla\Asistencia;

use App\Models\Labores;

/**
 * Relación entre labores y tipos de asistencia (labores de suspensión: DM, V, FR…).
 *
 * Un día no laborado se puede registrar de dos formas y ambas significan lo mismo:
 * - asistencia DM con total de horas (sin detalle), o
 * - asistencia A con detalle "labor 97 (Descanso médico), campo FDM, N horas" — también mezclado con
 *   trabajo real: A, 4 h de labor + 4 h de 97.
 * Esta clase traduce entre las dos para la BDD (FDM con el código de labor) y para las suspensiones PLAME.
 */
class PlanillaAsistenciaLaborConsulta
{
    private ?array $asistenciaPorLabor = null;
    private ?array $laborPorAsistencia = null;

    /** @return array<string, string> código de labor => código de asistencia */
    public function asistenciaPorLabor(): array
    {
        return $this->asistenciaPorLabor ??= Labores::whereNotNull('tipo_asistencia_codigo')
            ->where('tipo_asistencia_codigo', '<>', '')
            ->pluck('tipo_asistencia_codigo', 'codigo')
            ->mapWithKeys(fn($asistencia, $labor) => [(string) $labor => (string) $asistencia])
            ->all();
    }

    /**
     * Labor que representa cada código de asistencia (si hay varias, la de menor código).
     *
     * @return array<string, array{codigo: string, nombre: string}>
     */
    public function laborPorAsistencia(): array
    {
        if ($this->laborPorAsistencia !== null) {
            return $this->laborPorAsistencia;
        }
        $mapa = [];
        foreach (Labores::whereNotNull('tipo_asistencia_codigo')->where('tipo_asistencia_codigo', '<>', '')
            ->orderBy('codigo')->get(['codigo', 'nombre_labor', 'tipo_asistencia_codigo']) as $labor) {
            $mapa[$labor->tipo_asistencia_codigo] ??= ['codigo' => (string) $labor->codigo, 'nombre' => $labor->nombre_labor];
        }
        return $this->laborPorAsistencia = $mapa;
    }

    /** Código de asistencia que representa la labor, o null si es una labor de trabajo. */
    public function asistenciaDeLabor($codigoLabor): ?string
    {
        return $codigoLabor === null ? null : ($this->asistenciaPorLabor()[(string) $codigoLabor] ?? null);
    }

    /**
     * Horas del detalle de un día repartidas en trabajo y en cada código de suspensión.
     *
     * @param iterable<array{codigo_labor: mixed, minutos: int}> $tramos
     * @return array{trabajo: float, suspension: array<string, float>}
     */
    public function repartirHoras(iterable $tramos): array
    {
        $trabajo = 0.0;
        $suspension = [];
        foreach ($tramos as $t) {
            $horas = max(0, (int) $t['minutos']) / 60;
            $codigo = $this->asistenciaDeLabor($t['codigo_labor']);
            if ($codigo === null) {
                $trabajo += $horas;
            } else {
                $suspension[$codigo] = ($suspension[$codigo] ?? 0) + $horas;
            }
        }
        return ['trabajo' => $trabajo, 'suspension' => $suspension];
    }

    /**
     * Código con el que cuenta el día para las suspensiones: el de su asistencia, salvo que sea A y todo
     * su detalle sea de una sola labor de suspensión (p. ej. A + 8 h de 97 = día DM completo).
     * Un día mezclado (trabajo + suspensión) sigue siendo A: el PLAME declara días completos.
     */
    public function codigoEfectivo(?string $asistencia, iterable $tramos): ?string
    {
        if ($asistencia !== 'A') {
            return $asistencia;
        }
        $reparto = $this->repartirHoras($tramos);
        if ($reparto['trabajo'] < 0.01 && count($reparto['suspension']) === 1) {
            return array_key_first($reparto['suspension']);
        }
        return $asistencia;
    }
}

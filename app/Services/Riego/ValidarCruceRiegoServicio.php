<?php

namespace App\Services\Riego;

use App\Models\ConsolidadoRiego;
use App\Models\LaboresRiego;
use App\Models\ReporteDiarioRiego;
use App\Support\CalculoHelper;
use App\Support\FormatoHelper;
use Exception;

class ValidarCruceRiegoServicio
{
    /**
     * @return array Lista de mensajes de conflicto encontrados. Vacío = sin conflictos.
     */
    public function validar(array $data, string $fecha, int $consolidadoIdActual): array
    {
        $conflictos = [];

        $labores = LaboresRiego::all()->keyBy(fn($l) => mb_strtolower(trim($l->nombre_labor)));

        $entrantes = [];
        foreach ($data as $row) {
            if (empty($row[0]) || (isset($row[6]) && $row[6])) {
                continue;
            }

            $tipoLabor = trim($row[4] ?? 'Riego');
            $labor = $labores->get(mb_strtolower($tipoLabor));

            $entrantes[] = [
                'campo' => trim($row[0]),
                'inicio' => CalculoHelper::horaAMinutos(FormatoHelper::normalizarHora($row[1] ?? '00:00')),
                'fin' => CalculoHelper::horaAMinutos(FormatoHelper::normalizarHora($row[2] ?? '00:00')),
                'es_riego' => (bool) ($labor?->es_riego),
                'es_apoyo' => (bool) ($labor?->es_apoyo_riego),
                'consolidado_id' => $consolidadoIdActual,
            ];
        }

        $registrosOtros = ReporteDiarioRiego::whereDate('fecha', $fecha)
            ->where('por_acumulacion', false)
            ->where('consolidado_id', '!=', $consolidadoIdActual)
            ->get()
            ->map(function ($reg) use ($labores) {
                $labor = $labores->get(mb_strtolower(trim($reg->tipo_labor)));
                return [
                    'campo' => $reg->campo,
                    'inicio' => CalculoHelper::horaAMinutos($reg->hora_inicio),
                    'fin' => CalculoHelper::horaAMinutos($reg->hora_fin),
                    'es_riego' => (bool) ($labor?->es_riego),
                    'es_apoyo' => (bool) ($labor?->es_apoyo_riego),
                    'consolidado_id' => $reg->consolidado_id,
                ];
            })
            ->toArray();

        $todos = array_merge($entrantes, $registrosOtros);
        $porCampo = collect($todos)->groupBy('campo');

        foreach ($porCampo as $campo => $filas) {

            $riegosReales = $filas->where('es_riego', true)->values();
            $apoyos = $filas->where('es_apoyo', true)->values();

            for ($i = 0; $i < $riegosReales->count(); $i++) {
                for ($j = $i + 1; $j < $riegosReales->count(); $j++) {
                    $a = $riegosReales[$i];
                    $b = $riegosReales[$j];

                    if ($a['consolidado_id'] === $b['consolidado_id']) {
                        continue;
                    }

                    $seCruza = $a['inicio'] < $b['fin'] && $b['inicio'] < $a['fin'];

                    if ($seCruza) {
                        $nombreA = $this->nombreRegador($a['consolidado_id']);
                        $nombreB = $this->nombreRegador($b['consolidado_id']);

                        $conflictos[] = "El campo '{$campo}' tiene riego cruzado entre {$nombreA} " .
                            "(" . CalculoHelper::minutosAHora($a['inicio']) . "-" . CalculoHelper::minutosAHora($a['fin']) . ") y {$nombreB} " .
                            "(" . CalculoHelper::minutosAHora($b['inicio']) . "-" . CalculoHelper::minutosAHora($b['fin']) . "). " .
                            "Solo uno debería tener 'Riego'; el otro debería ser 'Apoyo en Riego'.";
                    }
                }
            }

            if ($apoyos->isNotEmpty()) {
                $coberturaRiego = CalculoHelper::fusionarIntervalos(
                    $riegosReales->map(fn($r) => ['inicio' => $r['inicio'], 'fin' => $r['fin']])->toArray()
                );

                foreach ($apoyos as $apoyo) {
                    $noCubierto = CalculoHelper::rangosNoCubiertos(
                        ['inicio' => $apoyo['inicio'], 'fin' => $apoyo['fin']],
                        $coberturaRiego
                    );

                    if (!empty($noCubierto)) {
                        $huecos = collect($noCubierto)
                            ->map(fn($h) => CalculoHelper::minutosAHora($h['inicio']) . '-' . CalculoHelper::minutosAHora($h['fin']))
                            ->implode(', ');

                        $conflictos[] = "En el campo '{$campo}' hay 'Apoyo en Riego' sin un riego real que lo respalde en: {$huecos}.";
                    }
                }
            }
        }

        return $conflictos;
    }

    private function nombreRegador(int $consolidadoId): string
    {
        return ConsolidadoRiego::find($consolidadoId)?->trabajador_nombre ?? "Regador #{$consolidadoId}";
    }
}
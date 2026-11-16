<?php

namespace App\Livewire\Traits;

use App\Services\Campania\Sector\CampaniaSectorRevision;

/**
 * Antes de guardar horas: cada labor de etapa debe caer en la etapa de algún grupo de hileras del campo ese día
 * (CampaniaSectorRevision, mapa de etapas de la campaña).
 *  - Labor que no cabe → ErrorControlado (no se guarda; el catch del componente lo muestra con errorAlert).
 *  - Labor de la etapa siguiente → "¿El grupo X pasa a infestación desde hoy?". "Sí" vuelve a llamar al listener
 *    $evento con $data + 'etapas_avanzar'; "Corregir" solo cierra.
 * Requiere LivewireAlert + HandlesAlerts y el listener registrado en el componente.
 */
trait ConActividadesCampo
{
    /** Devuelve true si se mostró la pregunta (no se debe guardar todavía). Lanza ErrorControlado si una labor no cabe. */
    protected function preguntarActividadesCampo(string $fecha, array $tramos, array $excluirPlan, array $excluirCuad, string $evento, array $data): bool
    {
        $avances = app(CampaniaSectorRevision::class)->revisar($fecha, $tramos, $excluirPlan, $excluirCuad);
        if (!$avances) {
            return false;
        }
        $soloRelleno = collect($avances)->every(fn($a) => ($a['tipo'] ?? '') === 'nueva_siembra');
        $this->confirm($soloRelleno ? '¿Es una nueva siembra (relleno)?' : (count($avances) === 1 ? '¿Pasa a la siguiente etapa?' : '¿Pasan a la siguiente etapa?'), [
            'html' => view('livewire.campo.partial.aviso-actividades', ['avances' => $avances, 'fecha' => $fecha])->render(),
            'icon' => 'question',
            'width' => 760,
            'showCancelButton' => true,
            'confirmButtonText' => 'Sí, es correcto: guardar',
            'cancelButtonText' => 'Corregir',
            'onConfirmed' => $evento,
            'data' => $data + ['etapas_avanzar' => $avances],
        ]);
        return true;
    }

    /** "Sí" de la pregunta: pasa las siembras a su nueva etapa o agrega el relleno (antes de guardar las horas). */
    protected function aceptarActividadesCampo(string $fecha, $data): void
    {
        $avances = CampaniaSectorRevision::limpiarAvances($data['etapas_avanzar'] ?? []);
        if ($avances) {
            app(CampaniaSectorRevision::class)->avanzar($avances);
        }
    }

    /** Tramos de filas Handsontable con columnas campo_N / labor_N. */
    protected static function tramosDeFilas(array $filas, string $origen, string $claveId, ?int $maxTramos = null): array
    {
        $tramos = [];
        foreach ($filas as $f) {
            foreach ($f as $k => $campo) {
                if (!preg_match('/^campo_(\d+)$/', (string) $k, $m) || trim((string) $campo) === '') {
                    continue;
                }
                if ($maxTramos !== null && (int) $m[1] > $maxTramos) {
                    continue;
                }
                $labor = $f["labor_{$m[1]}"] ?? null;
                if ($labor !== null && trim((string) $labor) !== '') {
                    $tramos[] = ['campo' => $campo, 'labor' => $labor, 'origen' => $origen, 'trabajador_id' => $f[$claveId] ?? null];
                }
            }
        }
        return $tramos;
    }
}

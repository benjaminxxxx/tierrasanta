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
        $hayRelleno = collect($avances)->contains(fn($a) => ($a['tipo'] ?? '') === 'nueva_siembra');
        $this->confirm($soloRelleno ? '¿Es una nueva siembra (relleno)?' : (count($avances) === 1 ? '¿Pasa a la siguiente etapa?' : '¿Pasan a la siguiente etapa?'), [
            'html' => view('livewire.campo.partial.aviso-actividades', ['avances' => $avances, 'fecha' => $fecha])->render(),
            'icon' => 'question',
            'width' => 760,
            'showCancelButton' => true,
            'confirmButtonText' => $soloRelleno ? 'Sí, es otra siembra: guardar' : 'Sí, es correcto: guardar',
            // Un relleno y un replante se registran igual: "es replante" guarda sin agregar la siembra
            'showDenyButton' => $hayRelleno,
            'denyButtonText' => 'No, es replante: guardar',
            'denyButtonColor' => '#475569',
            'cancelButtonText' => 'Corregir',
            'onConfirmed' => $evento,
            'onDenied' => $evento,
            'data' => $data + ['etapas_avanzar' => $avances],
        ]);
        return true;
    }

    /**
     * Respuesta de la pregunta (antes de guardar las horas): pasa las siembras a su nueva etapa y agrega el relleno.
     * $isDenied = "No, es replante": el relleno no se agrega (y esa tanda no se vuelve a proponer en la tarjeta).
     */
    protected function aceptarActividadesCampo(string $fecha, $data, bool $isDenied = false): void
    {
        $avances = CampaniaSectorRevision::limpiarAvances($data['etapas_avanzar'] ?? []);
        if ($isDenied) {
            foreach (collect($avances)->where('tipo', 'nueva_siembra') as $a) {
                $c = \App\Models\CampoCampania::find($a['campania_id']);
                if ($c) {
                    $c->forceFill(['rellenos_descartados' => array_values(array_unique(array_merge((array) ($c->rellenos_descartados ?? []), [$a['fecha']])))])->save();
                }
            }
            $avances = collect($avances)->reject(fn($a) => $a['tipo'] === 'nueva_siembra')->values()->all();
        }
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

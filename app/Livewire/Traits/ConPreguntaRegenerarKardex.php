<?php

namespace App\Livewire\Traits;

use App\Services\Almacen\Kardex\AlmacenKardexPendienteDetector;
use App\Services\Almacen\Kardex\AlmacenKardexRegeneracionProceso;
use Livewire\Attributes\On;

/**
 * Tras editar o eliminar algo que mueve el kardex: "El kardex cambió, ¿regenerar de una vez o más adelante?".
 * "Más adelante" lo deja en Tareas pendientes → Kardex por regenerar. Requiere LivewireAlert + HandlesAlerts.
 */
trait ConPreguntaRegenerarKardex
{
    /** @param array $claves [['producto_id','anio','tipo'?], …] */
    protected function preguntarRegenerarKardex(array $claves = [], array $kardexIds = []): void
    {
        try {
            $lista = app(AlmacenKardexRegeneracionProceso::class)->desactualizados($claves, $kardexIds);
            $abiertos = array_values(array_filter($lista, fn($k) => !$k['cerrado']));
            if (!$abiertos) {
                return;
            }
            $items = collect($abiertos)->map(fn($k) => '<li><b>' . e($k['producto']) . '</b> · ' . e($k['tipo']) . ' ' . $k['anio'] . '</li>')->implode('');
            $this->confirm('El kardex ha cambiado', [
                'html' => '<div class="text-left text-sm"><p class="text-graphite">Lo que acabas de guardar cambia estos kardex. '
                    . '¿Los regenero de una vez? Si eliges más adelante, quedan en Tareas pendientes → Kardex por regenerar.</p>'
                    . '<ul class="mt-2 max-h-48 list-disc space-y-0.5 overflow-y-auto pl-5 text-ink">' . $items . '</ul></div>',
                'icon' => 'question',
                'showCancelButton' => true,
                'confirmButtonText' => 'Regenerar ahora',
                'cancelButtonText' => 'Más adelante',
                'onConfirmed' => 'regenerarKardexAhora',
                'data' => ['kardex_ids' => array_column($abiertos, 'id')],
                'timer' => null,
            ]);
        } catch (\Throwable $e) {
            // La pregunta es una ayuda: si falla, el kardex sigue en Tareas pendientes
            report($e);
        }
    }

    #[On('regenerarKardexAhora')]
    public function regenerarKardexAhora($data = null): void
    {
        try {
            $ids = array_map('intval', (array) ($data['kardex_ids'] ?? []));
            $r = app(AlmacenKardexRegeneracionProceso::class)->regenerar($ids);
            app(AlmacenKardexPendienteDetector::class)->detectarTareasPendientes();
            if ($r['errores']) {
                $this->errorAlert(new \App\Exceptions\ErrorControlado(
                    ($r['regenerados'] ? 'Se regeneraron ' . count($r['regenerados']) . ' kardex, pero: ' : 'No se pudo regenerar: ') . implode(' | ', $r['errores'])));
                return;
            }
            $this->alert('success', 'Kardex regenerado: ' . implode(', ', $r['regenerados']) . '.', ['timer' => 6000]);
            $this->dispatch('kardexRegenerado', kardexIds: $ids);
            foreach ($ids as $id) {
                $this->dispatch('insumoKardexRefrescar', kardexId: $id);
            }
        } catch (\Throwable $e) {
            $this->errorAlert($e);
        }
    }
}

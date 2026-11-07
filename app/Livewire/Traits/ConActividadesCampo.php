<?php

namespace App\Livewire\Traits;

use App\Services\Campo\Actividad\CampoActividadProceso;

/**
 * Antes de guardar horas: "Este día los siguientes campos tienen las siguientes actividades, ¿es correcto?" cuando un
 * campo tiene más de un código y alguno no fue aceptado (CampoActividadProceso). "Sí, es correcto" vuelve a llamar al
 * listener $evento con $data + 'actividades_aceptar' (campo => códigos) para guardar; "Corregir" solo cierra.
 * Requiere LivewireAlert + HandlesAlerts y el listener registrado en el componente.
 */
trait ConActividadesCampo
{
    /** Devuelve true si se mostró la pregunta (no se debe guardar todavía). */
    protected function preguntarActividadesCampo(string $fecha, array $tramos, array $excluirPlan, array $excluirCuad, string $evento, array $data): bool
    {
        $campos = app(CampoActividadProceso::class)->revisar($fecha, $tramos, $excluirPlan, $excluirCuad);
        if (!$campos) {
            return false;
        }
        $this->confirm('¿Las actividades de cada campo son correctas?', [
            'html' => view('livewire.campo.partial.aviso-actividades', ['campos' => $campos, 'fecha' => $fecha])->render(),
            'icon' => 'question',
            'width' => 760,
            'showCancelButton' => true,
            'confirmButtonText' => 'Sí, es correcto',
            'cancelButtonText' => 'Corregir',
            'onConfirmed' => $evento,
            'data' => $data + ['actividades_aceptar' => collect($campos)->mapWithKeys(fn($c) => [$c['campo'] => array_column($c['codigos'], 'codigo')])->all()],
        ]);
        return true;
    }

    /** Guarda lo aceptado en la pregunta (si vino). */
    protected function aceptarActividadesCampo(string $fecha, $data): void
    {
        if (!empty($data['actividades_aceptar'])) {
            app(CampoActividadProceso::class)->aceptar($fecha, (array) $data['actividades_aceptar']);
        }
    }

    /** Después de guardar: quita códigos aceptados que ya nadie hizo y avisa. Devuelve el texto del aviso o null. */
    protected function limpiarActividadesCampo(string $fecha): ?string
    {
        try {
            $mensajes = app(CampoActividadProceso::class)->limpiar($fecha);
            return $mensajes ? implode(' ', $mensajes) : null;
        } catch (\Throwable $e) {
            report($e); // la limpieza es una ayuda: no debe frenar el guardado
            return null;
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

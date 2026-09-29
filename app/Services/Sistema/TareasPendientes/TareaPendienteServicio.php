<?php

namespace App\Services\Sistema\TareasPendientes;

use App\Models\TareaPendiente;

class TareaPendienteServicio
{
    /**
     * Registra o actualiza una tarea detectada, respetando:
     * - Si hay una tarea PENDIENTE abierta con el mismo tipo+clave, se actualiza.
     *   Si la nueva cantidad es 0, se cierra sola como 'completada' (pudo
     *   resolverse fuera de este flujo).
     * - Si NO hay una pendiente abierta (nunca existió, o la última ya se
     *   cerró) y la cantidad es 0 -> no se crea nada.
     * - Si NO hay una pendiente abierta y la cantidad es > 0 -> se crea una
     *   tarea NUEVA (nunca se reabre la cerrada; así conservas el historial
     *   de que el problema volvió a aparecer).
     */
    public function registrarOActualizar(array $datos): ?TareaPendiente
    {
        $abierta = TareaPendiente::where('tipo', $datos['tipo'])
            ->where('clave', $datos['clave'] ?? null)
            ->where('estado', 'pendiente')
            ->first();

        $cantidad = $datos['cantidad_afectados'] ?? 0;

        if ($abierta) {
            $abierta->update([
                'cantidad_afectados' => $cantidad,
                'titulo' => $datos['titulo'] ?? $abierta->titulo,
                'descripcion' => $datos['descripcion'] ?? $abierta->descripcion,
                'acciones' => $datos['acciones'] ?? $abierta->acciones,
                // Periodo de la tarea (null = sin periodo, se muestra siempre)
                'fecha_inicio' => array_key_exists('fecha_inicio', $datos) ? $datos['fecha_inicio'] : $abierta->fecha_inicio,
                'fecha_fin' => array_key_exists('fecha_fin', $datos) ? $datos['fecha_fin'] : $abierta->fecha_fin,
                'detectado_en' => now(),
                'estado' => $cantidad === 0 ? 'completada' : 'pendiente',
            ]);

            return $abierta;
        }

        if ($cantidad === 0) {
            return null;
        }

        return TareaPendiente::create(array_merge($datos, [
            'cantidad_afectados' => $cantidad,
            'estado' => 'pendiente',
            'detectado_en' => now(),
        ]));
    }
}
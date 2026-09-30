<?php

namespace App\Services\Campania\Cosecha;

use App\Models\CampoCampania;
use App\Models\TareaPendiente;
use App\Services\Sistema\TareasPendientes\TareaPendienteServicio;

/**
 * Tareas pendientes de cosecha:
 * - Campañas abiertas que ya deberían cerrarse (hay labores de la siguiente campaña después de la cosecha,
 *   o la cosecha terminó hace más de CampaniaCosechaReglas::DIAS_SUGERIR_CIERRE días).
 * - Ventas de cochinilla hechas después del plazo de su cosecha (15 días; 100 si fue para mamá).
 *
 * Son tareas de estado, no de periodo: se muestran en cualquier mes hasta que se resuelvan.
 */
class CampaniaCosechaDetector
{
    public const TIPO_CIERRE = 'campania-cierre-pendiente';
    public const TIPO_VENTA = 'campania-venta-fuera-plazo';

    public function __construct(
        private CampaniaCosechaConsulta $consulta,
        private TareaPendienteServicio $registrador,
    ) {
    }

    public function detectarTareasPendientes(?string $fechaInicio = null, ?string $fechaFin = null): void
    {
        $this->detectarCierres();
        $this->detectarVentasFueraDePlazo();
    }

    private function detectarCierres(): void
    {
        $abiertas = CampoCampania::whereNull('fecha_fin')->orderBy('campo')->get();
        $estados = $this->consulta->estados($abiertas);
        $porCerrar = $abiertas->filter(fn($c) => $estados[$c->id]['recomendar_cierre'] ?? null);

        $padre = $this->registrador->registrarOActualizar([
            'tipo' => self::TIPO_CIERRE,
            'clave' => 'general',
            'fecha_inicio' => null,
            'fecha_fin' => null,
            'titulo' => 'Campañas cosechadas que siguen abiertas',
            'descripcion' => 'La campaña termina con la cosecha. Cualquier labor posterior (preparado de tierra, limpieza, '
                . 'fumigación…) ya es de la siguiente campaña, así que debe cerrarse antes para que los costos no se mezclen.',
            'variante' => 'danger',
            'cantidad_afectados' => $porCerrar->count(),
            'servicio' => self::class,
            'metodo_detectar' => 'detectarTareasPendientes',
            'acciones' => [[
                'titulo' => 'Abrir resumen de campañas',
                'tipo_accion' => 'link',
                'url' => route('campania.resumen'),
            ]],
        ]);

        $vigentes = [];
        foreach ($porCerrar as $campania) {
            $estado = $estados[$campania->id];
            $clave = "campania-{$campania->id}";
            $vigentes[] = $clave;

            $cosecha = 'Cosecha: ' . formatear_fecha($estado['primera_cosecha'])
                . ($estado['dias_cosecha'] > 1 ? ' – ' . formatear_fecha($estado['ultima_cosecha']) : '')
                . " ({$estado['dias_cosecha']} día(s))" . ($estado['es_mama'] ? ', para mamá' : '') . '.';

            if ($estado['recomendar_cierre'] === 'actividades') {
                $posterior = $estado['actividad_posterior'];
                $titulo = "{$campania->campo} · {$campania->nombre_campania}: tiene actividades distintas después de la cosecha";
                $detalle = "Primera labor posterior: {$posterior['labor']} el " . formatear_fecha($posterior['fecha']) . '.';
                $variante = 'danger';
            } else {
                $titulo = "{$campania->campo} · {$campania->nombre_campania}: cosechada hace {$estado['dias_desde_cosecha']} días y sigue abierta";
                $detalle = '';
                $variante = 'warning';
            }

            $this->registrador->registrarOActualizar([
                'tipo' => self::TIPO_CIERRE,
                'clave' => $clave,
                'parent_id' => $padre?->id,
                'fecha_inicio' => null,
                'fecha_fin' => null,
                'titulo' => $titulo,
                'descripcion' => trim("{$cosecha} {$detalle} Se recomienda cerrar el "
                    . formatear_fecha($estado['fecha_cierre_sugerida']) . '.'),
                'variante' => $variante,
                'cantidad_afectados' => 1,
                'servicio' => self::class,
                'metodo_detectar' => 'detectarTareasPendientes',
                'acciones' => [[
                    'titulo' => 'Cerrar campaña',
                    'tipo_accion' => 'link',
                    'url' => route('campania.resumen', ['cerrar' => $campania->id]),
                ], [
                    'titulo' => 'Ver campaña',
                    'tipo_accion' => 'link',
                    'url' => route('campania.por_campo', ['campania' => $campania->id]),
                ]],
            ]);
        }

        $this->cerrarResueltas(self::TIPO_CIERRE, $vigentes);
    }

    private function detectarVentasFueraDePlazo(): void
    {
        $fueraDePlazo = $this->consulta->ventasFueraDePlazo();

        $padre = $this->registrador->registrarOActualizar([
            'tipo' => self::TIPO_VENTA,
            'clave' => 'general',
            'fecha_inicio' => null,
            'fecha_fin' => null,
            'titulo' => 'Ventas de cochinilla fuera de plazo',
            'descripcion' => 'La venta se carga a la campaña cosechada y debe hacerse hasta '
                . CampaniaCosechaReglas::DIAS_MAX_VENTA . ' días después de la cosecha (hasta '
                . CampaniaCosechaReglas::DIAS_MAX_VENTA_MAMA . ' días si la cochinilla se usó como mamá).',
            'variante' => 'warning',
            'cantidad_afectados' => $fueraDePlazo->count(),
            'servicio' => self::class,
            'metodo_detectar' => 'detectarTareasPendientes',
            'acciones' => [],
        ]);

        $vigentes = [];
        foreach ($fueraDePlazo as $fila) {
            $venta = $fila['venta'];
            $ingreso = $fila['ingreso'];
            $clave = "venta-{$venta->id}";
            $vigentes[] = $clave;
            $campania = $ingreso->campoCampania;

            $this->registrador->registrarOActualizar([
                'tipo' => self::TIPO_VENTA,
                'clave' => $clave,
                'parent_id' => $padre?->id,
                'fecha_inicio' => null,
                'fecha_fin' => null,
                'titulo' => "Venta del " . formatear_fecha($venta->fecha_venta) . " · lote {$ingreso->lote} ({$ingreso->campo})",
                'descripcion' => "Cosechado el " . formatear_fecha($ingreso->fecha)
                    . ($campania ? " (campaña {$campania->nombre_campania})" : '')
                    . ": vendido {$fila['dias']} días después; el plazo es {$fila['limite']} días"
                    . ($fila['es_mama'] ? ' (cosecha para mamá).' : '.'),
                'variante' => 'warning',
                'cantidad_afectados' => 1,
                'servicio' => self::class,
                'metodo_detectar' => 'detectarTareasPendientes',
                'acciones' => [],
            ]);
        }

        $this->cerrarResueltas(self::TIPO_VENTA, $vigentes);
    }

    /** Subtareas pendientes que ya no se detectan: se cierran solas. */
    private function cerrarResueltas(string $tipo, array $clavesVigentes): void
    {
        TareaPendiente::where('tipo', $tipo)
            ->where('estado', 'pendiente')
            ->whereNotNull('parent_id')
            ->whereNotIn('clave', $clavesVigentes)
            ->get()
            ->each(fn($vieja) => $this->registrador->registrarOActualizar([
                'tipo' => $tipo,
                'clave' => $vieja->clave,
                'cantidad_afectados' => 0,
            ]));
    }
}

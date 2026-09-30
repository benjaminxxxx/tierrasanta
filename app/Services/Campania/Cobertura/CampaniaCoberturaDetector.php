<?php

namespace App\Services\Campania\Cobertura;

use App\Models\TareaPendiente;
use App\Services\Sistema\TareasPendientes\TareaPendienteServicio;
use Illuminate\Support\Carbon;

/**
 * Tarea pendiente: actividades del periodo registradas en días que no pertenecen a ninguna campaña de su campo.
 * Sus costos no se cargan a ninguna campaña hasta que se ajusten las fechas (o se cree la campaña que falta).
 * Una tarea por periodo y una subtarea por campo.
 */
class CampaniaCoberturaDetector
{
    public const TIPO = 'campania-actividades-sin-campania';

    public function __construct(
        private CampaniaCoberturaConsulta $consulta,
        private TareaPendienteServicio $registrador,
    ) {
    }

    public function detectarTareasPendientes(?string $fechaInicio = null, ?string $fechaFin = null): void
    {
        $fechaInicio ??= now()->startOfMonth()->toDateString();
        $fechaFin ??= now()->endOfMonth()->toDateString();

        $porCampo = $this->consulta->sinCampania($fechaInicio, $fechaFin);
        $totalDias = array_sum(array_map('count', $porCampo));

        $padre = $this->registrador->registrarOActualizar([
            'tipo' => self::TIPO,
            'clave' => "{$fechaInicio}_{$fechaFin}",
            'fecha_inicio' => $fechaInicio,
            'fecha_fin' => $fechaFin,
            'titulo' => 'Actividades sin campaña — ' . Carbon::parse($fechaInicio)->translatedFormat('F Y'),
            'descripcion' => 'Labores registradas en días que no caen en ninguna campaña del campo. Sus costos no se cargan '
                . 'a ninguna campaña: ajusta el cierre de la anterior o el inicio de la siguiente (o crea la que falta).',
            'variante' => 'warning',
            'cantidad_afectados' => $totalDias,
            'servicio' => self::class,
            'metodo_detectar' => 'detectarTareasPendientes',
            'acciones' => [[
                'titulo' => 'Abrir resumen de campañas',
                'tipo_accion' => 'link',
                'url' => route('campania.resumen'),
            ]],
        ]);

        $vigentes = [];
        foreach ($porCampo as $campo => $dias) {
            $clave = "{$fechaInicio}_{$fechaFin}_campo-{$campo}";
            $vigentes[] = $clave;
            $fechas = array_keys($dias);
            $ejemplos = collect($dias)->take(4)
                ->map(fn($labores, $dia) => formatear_fecha($dia) . ': ' . implode(', ', array_slice($labores, 0, 2)))
                ->implode(' · ');

            $this->registrador->registrarOActualizar([
                'tipo' => self::TIPO,
                'clave' => $clave,
                'parent_id' => $padre?->id,
                'fecha_inicio' => $fechaInicio,
                'fecha_fin' => $fechaFin,
                'titulo' => "Campo {$campo}: " . count($dias) . ' día(s) sin campaña (' . formatear_fecha(reset($fechas))
                    . (count($fechas) > 1 ? ' – ' . formatear_fecha(end($fechas)) : '') . ')',
                'descripcion' => $ejemplos . (count($dias) > 4 ? '…' : ''),
                'variante' => 'warning',
                'cantidad_afectados' => count($dias),
                'servicio' => self::class,
                'metodo_detectar' => 'detectarTareasPendientes',
                'acciones' => [[
                    'titulo' => 'Ver campañas del campo',
                    'tipo_accion' => 'link',
                    'url' => route('campania.resumen', ['campo' => $campo]),
                ]],
            ]);
        }

        TareaPendiente::where('tipo', self::TIPO)
            ->where('estado', 'pendiente')
            ->whereNotNull('parent_id')
            ->where('fecha_inicio', $fechaInicio)
            ->whereNotIn('clave', $vigentes)
            ->get()
            ->each(fn($vieja) => $this->registrador->registrarOActualizar([
                'tipo' => self::TIPO,
                'clave' => $vieja->clave,
                'cantidad_afectados' => 0,
            ]));
    }
}

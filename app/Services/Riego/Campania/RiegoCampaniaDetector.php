<?php

namespace App\Services\Riego\Campania;

use App\Models\TareaPendiente;
use App\Services\Sistema\TareasPendientes\TareaPendienteServicio;
use Illuminate\Support\Carbon;

/**
 * Tarea pendiente (urgente): riegos del mes en campos sin campaña ese día. Su costo queda "SIN CAMPAÑA" y no llega a
 * ninguna campaña hasta que se cree la campaña o se corrija el campo en el registro de riego.
 * Una tarea por periodo y una subtarea por campo.
 */
class RiegoCampaniaDetector
{
    public const TIPO = 'riego-campo-sin-campania';

    public function __construct(
        private RiegoCampaniaConsulta $consulta,
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
            'titulo' => 'Riegos en campos sin campaña — ' . Carbon::parse($fechaInicio)->translatedFormat('F Y'),
            'descripcion' => 'Se registró riego en campos que ese día no tienen campaña: su costo queda "SIN CAMPAÑA". '
                . 'Corrígelo cuanto antes: crea o ajusta la campaña del campo, o corrige el campo en el registro de riego.',
            'variante' => 'danger',
            'cantidad_afectados' => $totalDias,
            'servicio' => self::class,
            'metodo_detectar' => 'detectarTareasPendientes',
            'acciones' => [[
                'titulo' => 'Abrir campañas',
                'tipo_accion' => 'link',
                'url' => route('campania.resumen'),
            ]],
        ]);

        $vigentes = [];
        foreach ($porCampo as $campo => $dias) {
            $clave = "{$fechaInicio}_{$fechaFin}_campo-{$campo}";
            $vigentes[] = $clave;
            $fechas = array_keys($dias);
            $horas = array_sum(array_column($dias, 'horas'));
            $ejemplos = collect($dias)->take(4)
                ->map(fn($d, $dia) => formatear_fecha($dia) . ': ' . implode(', ', array_slice($d['regadores'], 0, 2)))
                ->implode(' · ');

            $this->registrador->registrarOActualizar([
                'tipo' => self::TIPO,
                'clave' => $clave,
                'parent_id' => $padre?->id,
                'fecha_inicio' => $fechaInicio,
                'fecha_fin' => $fechaFin,
                'titulo' => "Campo {$campo}: " . count($dias) . ' día(s) de riego sin campaña, ' . rtrim(rtrim(number_format($horas, 2), '0'), '.') . ' h ('
                    . formatear_fecha(reset($fechas)) . (count($fechas) > 1 ? ' – ' . formatear_fecha(end($fechas)) : '') . ')',
                'descripcion' => $ejemplos . (count($dias) > 4 ? '…' : ''),
                'variante' => 'danger',
                'cantidad_afectados' => count($dias),
                'servicio' => self::class,
                'metodo_detectar' => 'detectarTareasPendientes',
                'acciones' => [
                    ['titulo' => 'Abrir riego del ' . Carbon::parse(reset($fechas))->format('d/m'), 'tipo_accion' => 'link',
                        'url' => route('riego.reporte_diario', ['fecha' => reset($fechas)])],
                    ['titulo' => 'Ver campañas del campo', 'tipo_accion' => 'link', 'url' => route('campania.resumen', ['campo' => $campo])],
                ],
            ]);
        }

        // Campos que ya se corrigieron: su subtarea queda en cero
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

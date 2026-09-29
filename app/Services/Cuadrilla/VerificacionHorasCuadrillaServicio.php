<?php

namespace App\Services\Cuadrilla;

use App\Models\TareaPendiente;
use App\Services\Sistema\TareasPendientes\TareaPendienteServicio;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Registros diarios de cuadrilla cuyo total de horas (lo que se paga) no coincide con la suma
 * de su detalle de horas por campo/labor (lo que va al costo por campo). Mientras no coincidan,
 * el costo de cuadrilla por campo no cuadra con lo pagado en el mes.
 *
 * Se detecta por periodo (rango de fechas): una tarea por periodo y una subtarea por día.
 */
class VerificacionHorasCuadrillaServicio
{
    public const TIPO = 'cuadrilla-horas-sin-detalle';
    private const TOLERANCIA_HORAS = 0.01;

    /**
     * Registros del rango que no cuadran: [fecha => [ ['registro_id', 'cuadrillero', 'total_horas', 'horas_detalle'], ... ]]
     */
    public function registrosQueNoCuadran(string $fechaInicio, string $fechaFin): array
    {
        return DB::table('cuad_registros_diarios as r')
            ->join('cuad_cuadrilleros as c', 'c.id', '=', 'r.cuadrillero_id')
            ->leftJoin('cuad_detalles_horas as d', 'd.registro_diario_id', '=', 'r.id')
            ->whereBetween('r.fecha', [$fechaInicio, $fechaFin])
            ->groupBy('r.id', 'r.fecha', 'r.total_horas', 'c.nombres')
            ->selectRaw('r.id as registro_id, r.fecha, c.nombres as cuadrillero, COALESCE(r.total_horas, 0) as total_horas, ROUND(COALESCE(SUM(TIMESTAMPDIFF(MINUTE, d.hora_inicio, d.hora_fin)), 0) / 60, 2) as horas_detalle')
            ->havingRaw('ABS(COALESCE(r.total_horas, 0) - ROUND(COALESCE(SUM(TIMESTAMPDIFF(MINUTE, d.hora_inicio, d.hora_fin)), 0) / 60, 2)) > ?', [self::TOLERANCIA_HORAS])
            ->orderBy('r.fecha')
            ->get()
            ->groupBy(fn($r) => Carbon::parse($r->fecha)->toDateString())
            ->map(fn($grupo) => $grupo->values()->all())
            ->all();
    }

    /**
     * @param string|null $fechaInicio por defecto, el mes actual
     */
    public function detectarTareasPendientes(?string $fechaInicio = null, ?string $fechaFin = null): void
    {
        $fechaInicio ??= now()->startOfMonth()->toDateString();
        $fechaFin ??= now()->endOfMonth()->toDateString();

        $porDia = $this->registrosQueNoCuadran($fechaInicio, $fechaFin);
        $total = array_sum(array_map('count', $porDia));
        $registrador = app(TareaPendienteServicio::class);

        $etiquetaPeriodo = $this->etiquetaPeriodo($fechaInicio, $fechaFin);

        $padre = $registrador->registrarOActualizar([
            'tipo' => self::TIPO,
            'clave' => "{$fechaInicio}_{$fechaFin}",
            'fecha_inicio' => $fechaInicio,
            'fecha_fin' => $fechaFin,
            'titulo' => "Cuadrilla: horas sin detalle — {$etiquetaPeriodo}",
            'descripcion' => 'Registros diarios cuyo total de horas no coincide con el detalle por campo/labor. '
                . 'El costo por campo se calcula del detalle, así que el mes no cuadrará con lo pagado.',
            'variante' => 'warning',
            'cantidad_afectados' => $total,
            'servicio' => self::class,
            'metodo_detectar' => 'detectarTareasPendientes',
            'acciones' => [],
        ]);

        $clavesVigentes = [];
        foreach ($porDia as $fecha => $registros) {
            $clave = "dia-{$fecha}";
            $clavesVigentes[] = $clave;
            $sinDetalle = count(array_filter($registros, fn($r) => (float) $r->horas_detalle == 0.0));
            $ejemplos = collect($registros)->take(3)
                ->map(fn($r) => "{$r->cuadrillero} ({$r->total_horas} h vs {$r->horas_detalle} h)")
                ->implode(', ');

            $registrador->registrarOActualizar([
                'tipo' => self::TIPO,
                'clave' => $clave,
                'fecha_inicio' => $fecha,
                'fecha_fin' => $fecha,
                'parent_id' => $padre?->id,
                'titulo' => Carbon::parse($fecha)->translatedFormat('D d/m/Y') . ' — ' . count($registros) . ' cuadrillero(s)'
                    . ($sinDetalle ? " ({$sinDetalle} sin detalle)" : ''),
                'descripcion' => $ejemplos . (count($registros) > 3 ? '…' : ''),
                'variante' => 'warning',
                'cantidad_afectados' => count($registros),
                'servicio' => self::class,
                'metodo_detectar' => 'detectarTareasPendientes',
                'acciones' => [[
                    'titulo' => 'Abrir registro diario',
                    'tipo_accion' => 'link',
                    'url' => route('cuadrilla.registro_diario', ['fecha' => $fecha]),
                ]],
            ]);
        }

        // Días del rango que ya cuadran: se cierran solas
        TareaPendiente::where('tipo', self::TIPO)
            ->where('estado', 'pendiente')
            ->whereNotNull('parent_id')
            ->whereBetween('fecha_inicio', [$fechaInicio, $fechaFin])
            ->whereNotIn('clave', $clavesVigentes)
            ->get()
            ->each(fn($vieja) => $registrador->registrarOActualizar([
                'tipo' => self::TIPO,
                'clave' => $vieja->clave,
                'cantidad_afectados' => 0,
            ]));
    }

    private function etiquetaPeriodo(string $fechaInicio, string $fechaFin): string
    {
        $inicio = Carbon::parse($fechaInicio);
        $fin = Carbon::parse($fechaFin);

        // Mes completo -> "julio 2026"; si no, el rango
        if ($inicio->isSameDay($inicio->copy()->startOfMonth()) && $fin->isSameDay($inicio->copy()->endOfMonth())) {
            return $inicio->translatedFormat('F Y');
        }
        return $inicio->format('d/m/Y') . ' – ' . $fin->format('d/m/Y');
    }
}

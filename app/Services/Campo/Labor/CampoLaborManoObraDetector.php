<?php

namespace App\Services\Campo\Labor;

use App\Models\Labores;
use App\Models\TareaPendiente;
use App\Services\Sistema\TareasPendientes\TareaPendienteServicio;
use Illuminate\Support\Facades\DB;

/**
 * Tarea pendiente: labores sin mano de obra (Cosecha, Siembra, Sanidad…). La mano de obra es el grupo con que
 * se arma "Costos de producción" de cada campaña: una labor sin grupo cae en "Sin mano de obra asignada".
 *
 * Subtareas solo para las labores que ya tienen costos registrados (resumen_costo_diarios), de mayor a menor
 * costo: son las que descuadran el reporte. Tarea de estado (sin periodo).
 */
class CampoLaborManoObraDetector
{
    public const TIPO = 'labor-sin-mano-obra';

    public function __construct(private TareaPendienteServicio $registrador)
    {
    }

    public function detectarTareasPendientes(?string $fechaInicio = null, ?string $fechaFin = null): void
    {
        $sinManoObra = Labores::where(fn($q) => $q->whereNull('codigo_mano_obra')->orWhere('codigo_mano_obra', ''))
            ->get(['id', 'codigo', 'nombre_labor']);

        // Costo registrado por código de labor (resumen_costo_diarios.labor guarda el código)
        $costos = DB::table('resumen_costo_diarios')
            ->whereIn('labor', $sinManoObra->pluck('codigo')->map(fn($c) => (string) $c))
            ->groupBy('labor')
            ->selectRaw('labor, SUM(costo_total) as costo')
            ->pluck('costo', 'labor');

        $conCosto = $sinManoObra->filter(fn($l) => isset($costos[(string) $l->codigo]))
            ->sortByDesc(fn($l) => (float) $costos[(string) $l->codigo])
            ->values();

        $url = route('campo.labores', ['mano_obra' => 'sin']);

        $padre = $this->registrador->registrarOActualizar([
            'tipo' => self::TIPO,
            'clave' => 'general',
            'fecha_inicio' => null,
            'fecha_fin' => null,
            'titulo' => 'Labores sin mano de obra',
            'descripcion' => "{$sinManoObra->count()} labor(es) sin mano de obra asignada; {$conCosto->count()} ya tienen costos registrados "
                . 'y aparecen como "Sin mano de obra asignada" en Costos de producción de las campañas.',
            'variante' => 'warning',
            'cantidad_afectados' => $sinManoObra->count(),
            'servicio' => self::class,
            'metodo_detectar' => 'detectarTareasPendientes',
            'acciones' => [[
                'titulo' => 'Asignar mano de obra',
                'tipo_accion' => 'link',
                'url' => $url,
            ]],
        ]);

        $vigentes = [];
        foreach ($conCosto as $labor) {
            $clave = "labor-{$labor->id}";
            $vigentes[] = $clave;

            $this->registrador->registrarOActualizar([
                'tipo' => self::TIPO,
                'clave' => $clave,
                'parent_id' => $padre?->id,
                'fecha_inicio' => null,
                'fecha_fin' => null,
                'titulo' => "{$labor->codigo} · {$labor->nombre_labor}",
                'descripcion' => 'S/ ' . number_format((float) $costos[(string) $labor->codigo], 2) . ' en costos registrados.',
                'variante' => 'warning',
                'cantidad_afectados' => 1,
                'servicio' => self::class,
                'metodo_detectar' => 'detectarTareasPendientes',
                'acciones' => [[
                    'titulo' => 'Ver labores',
                    'tipo_accion' => 'link',
                    'url' => $url,
                ]],
            ]);
        }

        TareaPendiente::where('tipo', self::TIPO)
            ->where('estado', 'pendiente')
            ->whereNotNull('parent_id')
            ->whereNotIn('clave', $vigentes)
            ->get()
            ->each(fn($vieja) => $this->registrador->registrarOActualizar([
                'tipo' => self::TIPO,
                'clave' => $vieja->clave,
                'cantidad_afectados' => 0,
            ]));
    }
}

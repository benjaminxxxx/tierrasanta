<?php

namespace App\Services\Campania\Registro;

use App\Models\Campo;
use App\Models\CampoCampania;
use App\Services\Campania\Cosecha\CampaniaCosechaConsulta;
use Illuminate\Support\Carbon;

/**
 * Datos de apoyo para registrar una campaña (solo lectura).
 */
class CampaniaRegistroConsulta
{
    public function __construct(private CampaniaCosechaConsulta $cosecha)
    {
    }

    /** Última campaña del campo que empezó antes de la fecha (hoy por defecto). */
    public function campaniaAnterior(string $campo, ?string $antesDe = null): ?CampoCampania
    {
        return CampoCampania::where('campo', $campo)
            ->whereDate('fecha_inicio', '<', $antesDe ?? now()->addDay()->toDateString())
            ->orderByDesc('fecha_inicio')
            ->first();
    }

    /**
     * Contexto del campo para el paso 1 del registro: área, últimas campañas, la vigente (si hay) con su estado
     * de cosecha, y valores sugeridos para la nueva.
     *
     * @return array{area: ?float, ultimas: array, vigente: ?array, sugerido: array{fecha_inicio: ?string, nombre_campania: string, variedad_tuna: ?string}}
     */
    public function contextoCampo(string $campo): array
    {
        $ultimas = CampoCampania::where('campo', $campo)->orderByDesc('fecha_inicio')->limit(4)->get();
        $estados = $this->cosecha->estados($ultimas);
        $vigente = $ultimas->first(fn($c) => $c->fecha_fin === null);
        $anterior = $ultimas->first();

        $resumir = fn(CampoCampania $c) => [
            'id' => $c->id,
            'nombre' => $c->nombre_campania,
            'inicio' => $c->fecha_inicio->toDateString(),
            'fin' => $c->fecha_fin?->toDateString(),
            'estado' => $estados[$c->id] ?? null,
        ];

        return [
            'area' => Campo::where('nombre', $campo)->value('area'),
            'ultimas' => $ultimas->map($resumir)->all(),
            'vigente' => $vigente ? $resumir($vigente) : null,
            'sugerido' => [
                'fecha_inicio' => $anterior?->fecha_fin?->copy()->addDay()->toDateString(),
                'nombre_campania' => $this->sugerirNombre($campo, $anterior?->nombre_campania),
                'variedad_tuna' => $anterior?->variedad_tuna,
            ],
        ];
    }

    /**
     * Correlativo del nombre anterior: "T.2024" → "T.2025" (y "T.2025-2", "-3"… si ya existe en el campo).
     * Sin nombre anterior: "T.{año actual}". Si el anterior no tiene año, se incrementa su último número.
     */
    public function sugerirNombre(string $campo, ?string $nombreAnterior): string
    {
        $existentes = CampoCampania::where('campo', $campo)->pluck('nombre_campania')
            ->map(fn($n) => mb_strtoupper($n))->all();

        if ($nombreAnterior && preg_match('/^(.*?)(\d{4})(-\d+)?$/', trim($nombreAnterior), $m)) {
            $base = $m[1] . ((int) $m[2] + 1);
        } elseif ($nombreAnterior && preg_match('/(\d+)(?!.*\d)/', $nombreAnterior)) {
            $base = preg_replace_callback('/(\d+)(?!.*\d)/',
                fn($n) => str_pad((int) $n[1] + 1, strlen($n[1]), '0', STR_PAD_LEFT), trim($nombreAnterior), 1);
        } else {
            $base = 'T.' . Carbon::now()->year;
        }

        $nombre = $base;
        for ($n = 2; in_array(mb_strtoupper($nombre), $existentes, true); $n++) {
            $nombre = "{$base}-{$n}";
        }
        return $nombre;
    }
}

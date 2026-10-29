<?php

namespace App\Services\Cuadrilla\Persona;

use Illuminate\Support\Carbon;

/**
 * Trabajador de planilla que el mismo día figura como cuadrillero. Es normal en algunos casos (Flavia, 29/10/2026):
 *  - trabajo_extra: además de su jornada de planilla trabajó en cuadrilla (p. ej. el "grupo tarde" que junta gente de
 *    varios grupos en uno solo);
 *  - otro_dia: trabajo de otro día anotado en esta fecha (p. ej. el domingo, registrado el lunes);
 *  - otra_persona: el sistema lo confundió por el nombre (homónimo).
 * La justificación es informativa: con ella ese día deja de salir en Tareas pendientes (riego de cuadrilleros y misma
 * persona en planilla y cuadrilla). No cambia pagos ni costos.
 */
class CuadrillaPersonaReglas
{
    public const MOTIVOS = [
        'trabajo_extra' => 'Trabajo extra',
        'otro_dia' => 'Trabajo de otro día',
        'otra_persona' => 'Es otra persona',
    ];

    public const AYUDA = [
        'trabajo_extra' => 'Además de su jornada de planilla trabajó en cuadrilla (p. ej. grupo tarde).',
        'otro_dia' => 'Trabajó otro día (p. ej. el domingo) y se anotó en esta fecha.',
        'otra_persona' => 'No es el trabajador de planilla: tiene un nombre parecido.',
    ];

    /** Fecha real sugerida para "otro día": el domingo anterior (o el mismo día si ya es domingo). */
    public static function domingoAnterior(string $fecha): string
    {
        $f = Carbon::parse($fecha);
        return ($f->isSunday() ? $f : $f->copy()->previous(Carbon::SUNDAY))->toDateString();
    }

    public static function texto(string $motivo, ?string $fechaReal = null): string
    {
        return (self::MOTIVOS[$motivo] ?? $motivo) . ($motivo === 'otro_dia' && $fechaReal ? ' (' . Carbon::parse($fechaReal)->format('d/m/Y') . ')' : '');
    }
}

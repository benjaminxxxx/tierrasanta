<?php

namespace App\Services\Cuadrilla\Bono;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Lecturas de bonos de cuadrilla: días que aún tienen el cálculo antiguo de destajo (descuenta horas del jornal).
 * Solo se informan; se corrigen a mano activando "Respeta el jornal" en Bonificaciones.
 */
class CuadrillaBonoConsulta
{
    /** Actividades con trabajadores en destajo antiguo: fecha, campo, labor, trabajadores, bono total. */
    public static function diasDestajoAntiguo(): Collection
    {
        return DB::table('cuad_bonos_actividades as b')
            ->join('actividad_metodos as m', 'm.id', '=', 'b.metodo_id')
            ->join('actividades as a', 'a.id', '=', 'b.actividad_id')
            ->whereNull('m.estandar')->where('m.respeta_jornal', false)
            ->groupBy('a.id', 'a.fecha', 'a.campo', 'a.codigo_labor', 'a.nombre_labor')
            ->orderByDesc('a.fecha')->orderBy('a.campo')
            ->selectRaw('a.id as actividad_id, a.fecha, a.campo, a.codigo_labor, a.nombre_labor, COUNT(*) as trabajadores, SUM(b.total_bono) as bono')
            ->get();
    }
}

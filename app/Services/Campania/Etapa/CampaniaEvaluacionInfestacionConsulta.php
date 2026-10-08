<?php

namespace App\Services\Campania\Etapa;

use App\Models\CampoCampania;
use App\Models\CochinillaInfestacion;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Evaluaciones de infestación (cochinilla por penca) de una campaña: se hacen a los días configurados después de la
 * infestación (Sistema → Configuración; antes 60, 75 y 100 fijos).
 *
 * La fecha base es la última infestación registrada de tipo "infestación" (la infestación dura varios días; la
 * reinfestación no cuenta). Si no hay de ese tipo, la última de cualquier tipo.
 *
 * Una evaluación está registrada si tiene su fecha o algún dato en las pencas. La pantalla guarda tres (1ª, 2ª, 3ª):
 * las configuradas después de la tercera solo salen en el texto.
 */
class CampaniaEvaluacionInfestacionConsulta
{
    /** Pasados estos días desde la última evaluación configurada ya no se avisa (la campaña ya se cosechó). */
    public const DIAS_GRACIA = 60;

    /** Columnas de las pencas de la 1ª, 2ª y 3ª evaluación. */
    private const COLUMNAS = [
        1 => ['eval_primera_piso_2', 'eval_primera_piso_3', 'eval_infest_fecha_primera'],
        2 => ['eval_segunda_piso_2', 'eval_segunda_piso_3', 'eval_infest_fecha_segunda'],
        3 => ['eval_tercera_piso_2', 'eval_tercera_piso_3', 'eval_infest_fecha_tercera'],
    ];

    public static function infestacionBase(CampoCampania $campania): ?CochinillaInfestacion
    {
        return $campania->infestaciones()->where('tipo_infestacion', 'infestacion')->latest('fecha')->first()
            ?? $campania->infestaciones()->latest('fecha')->first();
    }

    /**
     * @return array<int, array{numero:int, dias:int, fecha:?Carbon, registrable:bool, registrada:bool}>
     */
    public static function calendario(CampoCampania $campania, ?Carbon $base = null): array
    {
        $base ??= ($i = self::infestacionBase($campania)) ? Carbon::parse($i->fecha) : null;
        $pencas = $campania->relationLoaded('evalInfestacionPencas') ? $campania->evalInfestacionPencas : $campania->evalInfestacionPencas()->get();

        $calendario = [];
        foreach (CampaniaEtapaReglas::diasEvaluacionInfestacion() as $k => $dias) {
            $numero = $k + 1;
            $columnas = self::COLUMNAS[$numero] ?? null;
            $calendario[] = [
                'numero' => $numero,
                'dias' => $dias,
                'fecha' => $base?->copy()->addDays($dias),
                'registrable' => $columnas !== null,
                'registrada' => $columnas !== null && (
                    !empty($campania->{$columnas[2]})
                    || $pencas->contains(fn($p) => $p->{$columnas[0]} !== null || $p->{$columnas[1]} !== null)
                ),
            ];
        }
        return $calendario;
    }

    /**
     * Campañas a las que ya les tocó una evaluación de infestación que no tienen registrada.
     *
     * @return Collection<int, array{campania:CampoCampania, base:Carbon, faltantes:array}>
     */
    public function pendientes(?Carbon $hoy = null): Collection
    {
        $hoy ??= now()->startOfDay();
        $dias = CampaniaEtapaReglas::diasEvaluacionInfestacion();
        if (!$dias) {
            return collect();
        }
        $tope = min(count($dias), CampaniaEtapaReglas::EVALUACIONES_INFESTACION_REGISTRABLES);
        $ultimoDia = $dias[$tope - 1];

        $query = CampoCampania::query()->whereHas('infestaciones')->with('evalInfestacionPencas');
        if (!CampaniaEtapaReglas::avisarInfestacionEnCerradas()) {
            $query->whereNull('fecha_fin');
        }

        return $query->get()->map(function (CampoCampania $c) use ($hoy, $ultimoDia) {
            $base = Carbon::parse(self::infestacionBase($c)->fecha)->startOfDay();
            $transcurridos = (int) $base->diffInDays($hoy, false);
            if ($transcurridos > $ultimoDia + self::DIAS_GRACIA) {
                return null;
            }
            $faltantes = array_values(array_filter(self::calendario($c, $base),
                fn($e) => $e['registrable'] && !$e['registrada'] && $transcurridos >= $e['dias']));
            return $faltantes ? ['campania' => $c, 'base' => $base, 'transcurridos' => $transcurridos, 'faltantes' => $faltantes] : null;
        })->filter()->values();
    }
}

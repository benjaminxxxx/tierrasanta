<?php

namespace App\Services\Campania\Etapa;

use App\Models\Configuracion;
use Illuminate\Validation\ValidationException;

/**
 * Ciclo de una campaña: inicio → (siembra) → población de plantas → evaluaciones de brotes → infestación →
 * reinfestación → evaluación de infestación → cosecha de madres → cosecha → cierre.
 *
 * No es una regla estricta: sirve para que el sistema sugiera lo que normalmente toca. Lo configurable (tabla
 * `configuracion`) son los días promedio desde el inicio de la campaña en que se hace cada evaluación de brotes
 * (p. ej. 35, 50, 65, 80: cuatro evaluaciones antes de la infestación).
 */
class CampaniaEtapaReglas
{
    public const CONFIG_DIAS_EVALUACION_BROTES = 'campania_evaluacion_brotes_dias';
    public const CONFIG_DIAS_MAXIMO_SUGERENCIA = 'campania_evaluacion_brotes_dias_maximo';

    /** Pasado este tiempo desde el inicio ya no se sugieren evaluaciones (campañas viejas o abandonadas). */
    public const DIAS_MAXIMO_SUGERENCIA = 365;

    /** Etapas en el orden del proceso: clave => [nombre, color] */
    public const ETAPAS = [
        'inicio' => ['Inicio de campaña', '#64748B'],
        'siembra' => ['Siembra', '#16A34A'],
        'poblacion' => ['Población de plantas', '#0EA5E9'],
        'brotes' => ['Evaluación de brotes', '#84CC16'],
        'infestacion' => ['Infestación', '#DC2626'],
        'reinfestacion' => ['Reinfestación', '#F97316'],
        'eval_infestacion' => ['Evaluación de infestación', '#A855F7'],
        'cosecha_madres' => ['Cosecha de madres', '#DB2777'],
        'cosecha' => ['Cosecha', '#CA8A04'],
        'cierre' => ['Cierre', '#334155'],
    ];

    /**
     * Días desde el inicio de la campaña de cada evaluación de brotes (1ª, 2ª…), de menor a mayor.
     *
     * @return int[]
     */
    public static function diasEvaluacionBrotes(): array
    {
        $valor = json_decode((string) Configuracion::find(self::CONFIG_DIAS_EVALUACION_BROTES)?->valor, true);
        return is_array($valor) ? self::limpiarDias($valor) : [];
    }

    public static function diasMaximoSugerencia(): int
    {
        $valor = (int) Configuracion::find(self::CONFIG_DIAS_MAXIMO_SUGERENCIA)?->valor;
        return $valor > 0 ? $valor : self::DIAS_MAXIMO_SUGERENCIA;
    }

    /** @param array<int, mixed> $dias */
    public static function guardar(array $dias, $diasMaximo): void
    {
        $limpios = self::limpiarDias($dias);
        $originales = array_values(array_filter($dias, fn($d) => $d !== null && $d !== ''));
        if (count($limpios) !== count($originales)) {
            throw ValidationException::withMessages(['dias' => 'Cada evaluación necesita un número de días mayor que cero y distinto de los demás.']);
        }
        $diasMaximo = (int) $diasMaximo;
        if ($diasMaximo <= 0 || ($limpios && $diasMaximo < max($limpios))) {
            throw ValidationException::withMessages(['dias_maximo' => 'El tope debe ser mayor que el día de la última evaluación.']);
        }

        Configuracion::updateOrCreate(['codigo' => self::CONFIG_DIAS_EVALUACION_BROTES], [
            'valor' => json_encode($limpios),
            'descripcion' => 'Días promedio desde el inicio de la campaña de cada evaluación de brotes (1ª, 2ª…)',
        ]);
        Configuracion::updateOrCreate(['codigo' => self::CONFIG_DIAS_MAXIMO_SUGERENCIA], [
            'valor' => (string) $diasMaximo,
            'descripcion' => 'Días desde el inicio de la campaña después de los cuales ya no se sugieren evaluaciones de brotes',
        ]);
    }

    /** @return int[] enteros positivos, sin repetir, de menor a mayor */
    private static function limpiarDias(array $dias): array
    {
        $enteros = array_filter(array_map(fn($d) => is_numeric($d) ? (int) $d : 0, $dias), fn($d) => $d > 0);
        $enteros = array_values(array_unique($enteros));
        sort($enteros);
        return $enteros;
    }
}

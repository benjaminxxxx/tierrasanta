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

    // ------------------------------------------------------------------ evaluaciones de infestación

    public const CONFIG_DIAS_EVALUACION_INFESTACION = 'campania_evaluacion_infestacion_dias';
    public const CONFIG_INFESTACION_AVISAR_CERRADAS = 'campania_evaluacion_infestacion_avisar_cerradas';
    /** Lo que decía la pantalla de evaluación de infestación antes de ser configurable. */
    public const DIAS_EVALUACION_INFESTACION = [60, 75, 100];
    /** La pantalla registra hasta tres evaluaciones por campaña (1ª, 2ª y 3ª). */
    public const EVALUACIONES_INFESTACION_REGISTRABLES = 3;

    /**
     * Días después de la infestación en que se hace cada evaluación de infestación (1ª, 2ª…), de menor a mayor.
     *
     * @return int[]
     */
    public static function diasEvaluacionInfestacion(): array
    {
        $guardado = Configuracion::find(self::CONFIG_DIAS_EVALUACION_INFESTACION)?->valor;
        if ($guardado === null) {
            return self::DIAS_EVALUACION_INFESTACION;
        }
        $valor = json_decode((string) $guardado, true);
        return is_array($valor) ? self::limpiarDias($valor) : [];
    }

    public static function avisarInfestacionEnCerradas(): bool
    {
        return (bool) Configuracion::find(self::CONFIG_INFESTACION_AVISAR_CERRADAS)?->valor;
    }

    /** "60, 75 y 100" (para los textos de pantalla). */
    public static function textoDias(array $dias): string
    {
        if (count($dias) <= 1) {
            return (string) ($dias[0] ?? '');
        }
        return implode(', ', array_slice($dias, 0, -1)) . ' y ' . end($dias);
    }

    /** @param array<int, mixed> $dias */
    public static function guardarInfestacion(array $dias, bool $avisarCerradas): void
    {
        $limpios = self::limpiarDias($dias);
        $originales = array_values(array_filter($dias, fn($d) => $d !== null && $d !== ''));
        if (count($limpios) !== count($originales)) {
            throw ValidationException::withMessages(['dias_infestacion' => 'Cada evaluación necesita un número de días mayor que cero y distinto de los demás.']);
        }
        Configuracion::updateOrCreate(['codigo' => self::CONFIG_DIAS_EVALUACION_INFESTACION], [
            'valor' => json_encode($limpios),
            'descripcion' => 'Días después de la infestación de cada evaluación de infestación (1ª, 2ª…)',
        ]);
        Configuracion::updateOrCreate(['codigo' => self::CONFIG_INFESTACION_AVISAR_CERRADAS], [
            'valor' => $avisarCerradas ? '1' : '0',
            'descripcion' => 'Avisar evaluaciones de infestación pendientes también en campañas cerradas',
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

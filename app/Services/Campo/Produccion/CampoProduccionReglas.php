<?php

namespace App\Services\Campo\Produccion;

use App\Services\Campo\Labor\CampoLaborVigenciaConsulta;

/**
 * Reglas de la producción de campo (cantidades por labor, con o sin bono). Un solo lugar para: qué unidad produce una
 * labor, qué se compara con el ingreso de cochinilla y desde qué diferencia se advierte.
 */
class CampoProduccionReglas
{
    /** Diferencia (%) entre lo cosechado en campo y lo ingresado desde la que se advierte, y desde la que es anomalía. */
    public const PCT_AVISO = 10;
    public const PCT_ANOMALIA = 25;

    /** Unidad que se compara con el ingreso de cochinilla (kilos). */
    public const UNIDAD_CUADRE = 'KG';

    /** Rol de campaña de las labores cuyos kilos se comparan con el ingreso (cosecha, poda, poda mama). */
    public const ROL_CUADRE = 'cosecha';

    /** Tipos de ingreso de cochinilla que corresponden a cosecha en campo (no se cuentan los infestadores de malla). */
    public const INGRESOS_CUADRE = ['poda_mama', 'poda_cosecha'];

    /** Nombres antiguos (texto libre) → código del catálogo; los usa la normalización y la lectura del Excel. */
    public const EQUIVALENCIAS = ['KG' => 'KG', 'KG.' => 'KG', 'KILOS' => 'KG', 'LAVADORES' => 'LAVADOR', 'LAVADEROS' => 'LAVADOR',
        'LAVADOR' => 'LAVADOR', 'LAVADERO' => 'LAVADOR', 'HA' => 'HA', 'CAJAS' => 'CAJA', 'CAJA' => 'CAJA',
        'KILO' => 'KG', 'KILOGRAMOS' => 'KG', 'JABAS' => 'JABA', 'PLANTAS' => 'PLANTA', 'SURCOS' => 'SURCO',
        'UNIDADES' => 'UNIDAD', 'HECTAREAS' => 'HA', 'HECTÁREAS' => 'HA'];

    /** Unidades que se sugieren aunque el catálogo esté vacío: código => [nombre, decimales]. */
    public const SUGERIDAS = ['KG' => ['Kilogramos', 2], 'LAVADOR' => ['Lavadores', 0], 'CAJA' => ['Cajas', 0],
        'JABA' => ['Jabas', 0], 'PLANTA' => ['Plantas', 0], 'SURCO' => ['Surcos', 0], 'HA' => ['Hectáreas', 2], 'UNIDAD' => ['Unidades', 0]];

    /** Sugerencias para el campo de unidad: las del catálogo (activas) y las sugeridas que aún no están. codigo => nombre */
    public static function opcionesUnidad(): array
    {
        $catalogo = \App\Models\ProduccionUnidad::where('activo', true)->orderBy('codigo')->pluck('nombre', 'codigo')->all();
        return $catalogo + array_map(fn($s) => $s[0], array_diff_key(self::SUGERIDAS, $catalogo));
    }

    /**
     * La unidad escrita en el formulario de labores: si no está en el catálogo, se agrega (texto libre con sugerencias).
     * Devuelve el código o null si está vacía.
     */
    public static function asegurarUnidad(?string $unidad): ?string
    {
        $codigo = self::codigo($unidad);
        if (!$codigo) {
            return null;
        }
        $codigo = mb_substr($codigo, 0, 20);
        $existente = \App\Models\ProduccionUnidad::find($codigo);
        if (!$existente) {
            [$nombre, $decimales] = self::SUGERIDAS[$codigo] ?? [mb_convert_case(mb_strtolower($codigo), MB_CASE_TITLE), 2];
            \App\Models\ProduccionUnidad::create(['codigo' => $codigo, 'nombre' => $nombre, 'decimales' => $decimales, 'activo' => true]);
        } elseif (!$existente->activo) {
            $existente->update(['activo' => true]);
        }
        return $codigo;
    }

    /** Código de catálogo para un texto de unidad (null si está vacío o es "0"). */
    public static function codigo(?string $unidad): ?string
    {
        $u = mb_strtoupper(trim((string) $unidad));
        if ($u === '' || $u === '0') {
            return null;
        }
        return self::EQUIVALENCIAS[$u] ?? $u;
    }

    /**
     * Regla de la labor: si registra producción, la unidad es obligatoria y debe existir en el catálogo; si no, la unidad
     * queda vacía. Devuelve [registra, unidad] normalizados o el mensaje de error (string).
     * @return array{0: bool, 1: ?string}|string
     */
    public static function unidadDeLaborValida($registra, ?string $unidad): array|string
    {
        $registra = filter_var($registra, FILTER_VALIDATE_BOOLEAN);
        if (!$registra) {
            return [false, null];
        }
        $codigo = self::codigo($unidad);
        if (!$codigo) {
            return 'La labor registra producción: indica su unidad (KG, LAVADOR, CAJA…).';
        }
        if (!\Illuminate\Support\Facades\DB::table('produccion_unidades')->where('codigo', $codigo)->exists()) {
            return "La unidad \"{$codigo}\" no existe en el catálogo de unidades de producción (Excel de recursos).";
        }
        return [true, $codigo];
    }

    /** Unidad de producción de la labor (por código) en esa fecha; null = la labor no produce. */
    public static function unidadDeLabor($codigoLabor, $fecha = null): ?string
    {
        return self::codigo(app(CampoLaborVigenciaConsulta::class)->labor($codigoLabor, $fecha)?->unidades);
    }

    /** Estado del cuadre según la diferencia: ok, aviso, anomalia, sin_ingreso, sin_produccion. */
    public static function estadoCuadre(float $campo, float $ingreso): string
    {
        if ($campo <= 0 && $ingreso <= 0) {
            return 'ok';
        }
        if ($ingreso <= 0) {
            return 'sin_ingreso';
        }
        if ($campo <= 0) {
            return 'sin_produccion';
        }
        $pct = abs($campo - $ingreso) / $ingreso * 100;
        return $pct >= self::PCT_ANOMALIA ? 'anomalia' : ($pct >= self::PCT_AVISO ? 'aviso' : 'ok');
    }
}

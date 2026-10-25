<?php

namespace App\Services\Sistema\LibroExcel;

use Illuminate\Support\Carbon;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

/**
 * Base de las reglas de un libro Excel del sistema (recursos, insumos, empleados). Cada libro define:
 * - PREFIJO_ARCHIVO ("RECURSOS") y TITULO ("Excel de recursos").
 * - HOJAS: clave => [título, columnas que identifican la fila, columnas]. Columna: t = encabezado, tipo (texto, entero,
 *   decimal, fecha, sino), req = obligatoria, op = lista (OPCIONES), ref = debe existir en otra hoja (hoja.columna),
 *   def = valor si viene vacía, lectura = solo informativa (no se importa), unico = no se repite.
 * - OPCIONES: listas desplegables.
 * - MATRIZ / TITULO_MATRIZ: hoja de matriz opcional (recursos: roles × permisos).
 * Las hojas van en orden de dependencias: lo referido antes que lo que lo usa (así se guardan).
 */
abstract class SistemaLibroExcelReglas
{
    public const PREFIJO_ARCHIVO = 'LIBRO';
    public const TITULO = 'Excel';
    public const HOJAS = [];
    public const OPCIONES = [];
    public const MATRIZ = null;
    public const TITULO_MATRIZ = null;

    /** @param string[] $hojas claves de HOJAS (o MATRIZ) @return string[] títulos */
    public static function titulos(array $hojas): array
    {
        return array_map(fn($h) => $h === static::MATRIZ ? static::TITULO_MATRIZ : (static::HOJAS[$h][0] ?? $h), $hojas);
    }

    /** "RECURSOS.xlsx" o, con solo algunas hojas, "RECURSOS - Labores.xlsx" (el nombre de la primera hoja). */
    public static function nombreArchivo(?array $hojas = null): string
    {
        return static::PREFIJO_ARCHIVO . ($hojas ? ' - ' . static::titulos($hojas)[0] : '') . '.xlsx';
    }

    /** @return string[] todas las claves válidas (hojas y matriz) */
    /**
     * Actualización en cadena: las hojas pedidas más las que referencian (columna 'ref'), en cadena y en el orden del
     * libro. Así bajar o subir solo "Labores" trae también "Unidades de producción", "Mano de obra"… y nunca falla
     * por "carga primero…". La matriz (si se pidió) se conserva.
     * @param string[] $hojas
     * @return string[]
     */
    public static function conDependencias(array $hojas): array
    {
        $pendientes = $hojas;
        $todas = [];
        while ($pendientes) {
            $h = array_shift($pendientes);
            if (isset($todas[$h])) {
                continue;
            }
            $todas[$h] = true;
            foreach (static::HOJAS[$h][2] ?? [] as $columna) {
                if (isset($columna['ref'])) {
                    $pendientes[] = explode('.', $columna['ref'])[0];
                }
            }
        }
        return array_values(array_filter(static::claves(), fn($c) => isset($todas[$c])));
    }

    public static function claves(): array
    {
        return array_merge(array_keys(static::HOJAS), static::MATRIZ ? [static::MATRIZ] : []);
    }

    /** Valor normalizado para comparar y guardar (null si vacío). */
    public static function normalizar(mixed $valor, string $tipo): ?string
    {
        if ($valor === null || (is_string($valor) && trim($valor) === '')) {
            return null;
        }
        return match ($tipo) {
            'entero' => is_numeric($valor) ? (string) (int) round((float) $valor) : trim((string) $valor),
            'decimal' => is_numeric($valor) ? self::numero((float) $valor) : trim((string) $valor),
            'fecha' => self::fecha($valor),
            'sino' => self::sino($valor),
            default => is_float($valor) && floor($valor) == $valor ? (string) (int) $valor : trim(preg_replace('/\s+/u', ' ', (string) $valor)),
        };
    }

    public static function numero(float $n): string
    {
        $s = rtrim(rtrim(number_format(round($n, 6), 6, '.', ''), '0'), '.');
        return $s === '-0' ? '0' : $s;
    }

    private static function sino(mixed $v): string
    {
        $t = mb_strtoupper(trim((string) $v));
        return in_array($t, ['SI', 'SÍ', 'S', 'X', '1', 'TRUE', 'VERDADERO', 'YES'], true) ? 'SI'
            : (in_array($t, ['NO', 'N', '0', 'FALSE', 'FALSO'], true) ? 'NO' : $t);
    }

    private static function fecha(mixed $v): string
    {
        if (is_numeric($v)) {
            return Carbon::instance(ExcelDate::excelToDateTimeObject((float) $v))->toDateString();
        }
        if ($v instanceof \DateTimeInterface) {
            return Carbon::instance($v)->toDateString();
        }
        $t = trim((string) $v);
        foreach (['d/m/Y', 'Y-m-d', 'd-m-Y', 'd/m/y'] as $formato) {
            $f = \DateTime::createFromFormat('!' . $formato, $t);
            if ($f && $f->format($formato) === $t) {
                return $f->format('Y-m-d');
            }
        }
        return $t; // queda como texto: el análisis lo marca como fecha inválida
    }

    public static function esFecha(?string $v): bool
    {
        return $v === null || (bool) preg_match('/^\d{4}-\d{2}-\d{2}$/', $v);
    }

    /** Booleano de BD → SI/NO */
    public static function sn(mixed $v): string
    {
        return $v ? 'SI' : 'NO';
    }
}

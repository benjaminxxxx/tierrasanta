<?php

namespace App\Services\Cochinilla\Excel;

/**
 * Formato oficial del Excel de cochinilla (un archivo por año: "COCHINILLA {año}.xlsx"), reemplaza al Excel histórico
 * pesado (COCHINILLA 2026 al ….xlsx, con macros y columnas de fórmula). Reglas del usuario (20/10/2026):
 *
 * - Solo las hojas que se usan (las visibles del Excel histórico): Ingreso, Venteado, Filtrado e Infestación se importan;
 *   Cosecha Mamas y PENCA aún no tienen tabla: viajan en el archivo (se conservan del último archivo subido de ese año)
 *   y al importar solo se muestran; RESUMEN (consolidado por lote) lo arma el sistema al descargar. Las hojas ocultas
 *   del histórico (Stock, Despachos, Kardex Físico, Infestadores, Producto terminado…) no se usan desde 2017.
 * - Infestación con el formato de la "Propuesta 2" (INFESTACIONES.xlsx): una fila por método.
 * - Ingreso igual que hoy: filas de sublote (1457.1, 1457.2…) y una fila del lote con el total (1457). El total del
 *   lote se escribe como SUMA de sus sublotes al exportar.
 * - La campaña NO sale del Excel (la columna antigua venía de una tabla fija por campo): la asigna el sistema por campo
 *   + fecha (triggers de cochinilla_ingresos / cochinilla_infestaciones). En el Excel es solo informativa.
 * - Al importar se sincroniza: lote (ingreso) nuevo → se crea; con diferencias → se actualiza al confirmar; lotes del
 *   sistema que no están en el Excel → se listan, no se borran. Venteado / filtrado se reemplazan por lote presente en el
 *   Excel; infestaciones, por campo + fecha presentes en el Excel.
 * - Año de cada dato: el lote por su fecha de ingreso; venteado y filtrado van en el archivo del año de su lote (aunque
 *   se procesen al año siguiente); infestación por su fecha.
 */
class CochinillaExcelReglas
{
    public const HOJA_INGRESO = 'Ingreso';
    public const HOJA_VENTEADO = 'Venteado';
    public const HOJA_FILTRADO = 'Filtrado';
    public const HOJA_INFESTACION = 'Infestación';
    public const HOJA_LISTAS = 'Listas';
    public const HOJA_RESUMEN = 'RESUMEN';

    public const COLUMNAS_INGRESO = ['Lote', 'Sublote', 'Fecha', 'Campo', 'Observación', 'Kilos', 'Campaña (la asigna el sistema)'];
    public const COLUMNAS_VENTEADO = ['Lote', 'Fecha proceso', 'Kilos ingresados', 'Limpia', 'Basura', 'Polvillo'];
    public const COLUMNAS_FILTRADO = ['Lote', 'Fecha proceso', 'Kilos ingresados', 'Primera', 'Segunda', 'Tercera', 'Piedra', 'Basura'];
    /** Propuesta 2 (INFESTACIONES.xlsx). Área, campaña y los cálculos por ha los pone el sistema / fórmulas. */
    public const COLUMNAS_INFESTACION = ['Tipo', 'Fecha infestación', 'Campo', 'Área', 'Campaña', 'Kg madres', 'Kg madres/ha', 'Origen campo',
        'Método', 'Und x envase', 'Envases', 'Infestadores', 'Madres/infest.', 'Infestadores/ha', '% Malla Rashel', 'Procedencia infestadores'];

    public const COLUMNAS_RESUMEN = ['Lote', 'Fecha ingreso', 'Campo', 'Campaña', 'Observación', 'Kilos', 'Sublotes',
        'Venteado kilos', 'Limpia', 'Basura', 'Polvillo', 'Filtrado kilos', 'Primera', 'Segunda', 'Tercera', 'Piedra', 'Basura filtrado', '% Primera'];

    public const TIPOS_INFESTACION = ['infestacion', 'reinfestacion'];
    public const METODOS = ['carton', 'tubo', 'malla'];

    /**
     * Hojas que se conservan sin importarse: nombre => [filas de encabezado del Excel antiguo, fila donde empiezan los
     * datos, última columna]. Los encabezados de varias filas se unen en uno ("PRIMERA Ingreso").
     */
    public const HOJAS_EXTRA = [
        'Cosecha Mamas' => ['antigua' => 'Cosecha Mamas', 'encabezado' => [7], 'datos' => 10, 'columnas' => 8],
        'PENCA' => ['antigua' => 'PENCA', 'encabezado' => [8], 'datos' => 11, 'columnas' => 10],
    ];

    public static function nombreArchivo(int $anio): string
    {
        return "COCHINILLA {$anio}.xlsx";
    }

    /** Código de observación desde el texto del Excel: "Poda - Mama" / "Poda – Mama" → poda_mama. */
    public static function codigoObservacion(?string $texto): ?string
    {
        $t = mb_strtolower(trim((string) $texto));
        if ($t === '') {
            return null;
        }
        $t = strtr($t, ['á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ñ' => 'n', '–' => '-', '—' => '-']);
        return trim(preg_replace('/[^a-z0-9]+/', '_', $t), '_');
    }

    /** Lote como entero ("004" → 4). null si no es un número. */
    public static function lote($valor): ?int
    {
        $v = trim((string) $valor);
        return $v !== '' && preg_match('/^\d+(\.0+)?$/', $v) ? (int) $v : null;
    }

    /** Sublote normalizado "1457.1" (null si no tiene forma lote.n). */
    public static function sublote($valor): ?string
    {
        $v = trim((string) $valor);
        if (!preg_match('/^(\d+)[.,](\d+)$/', $v, $m)) {
            return null;
        }
        return ((int) $m[1]) . '.' . ((int) $m[2]);
    }

    /** Lote al que pertenece un sublote ("1457.3" → 1457). */
    public static function loteDeSublote(string $sublote): int
    {
        return (int) explode('.', $sublote)[0];
    }

    public static function kilos($v): ?float
    {
        if ($v === null || $v === '') {
            return null;
        }
        return is_numeric($v) ? round((float) $v, 2) : null;
    }
}

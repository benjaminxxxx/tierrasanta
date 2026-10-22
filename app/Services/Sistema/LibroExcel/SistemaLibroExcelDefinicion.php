<?php

namespace App\Services\Sistema\LibroExcel;

use Illuminate\Support\Str;

/**
 * Lo que cada libro Excel aporta al motor común (SistemaLibroExcel / Analisis / Proceso):
 * - reglas(): su clase de reglas (hojas, listas, nombre de archivo).
 * - filasSistema(): las filas actuales de cada hoja (valores crudos; el motor los normaliza).
 * - guardar{Hoja}(array $datos): cómo se guarda una fila nueva o con cambios.
 * - Ganchos opcionales: reglas propias por fila, preparación de filas, guardado por grupos y una hoja de matriz.
 */
abstract class SistemaLibroExcelDefinicion
{
    /** @return class-string<SistemaLibroExcelReglas> */
    abstract public function reglas(): string;

    /** @return array<string, array<int, array<string, mixed>>> hoja => filas del sistema */
    abstract public function filasSistema(): array;

    /** Permiso para descargar y subir el libro completo (en Configuración / escritorio). */
    abstract public function permiso(): string;

    /** Líneas propias del "Léeme" (reglas del libro). @return string[] */
    public function leeme(): array
    {
        return [];
    }

    /** Ajusta una fila leída antes de validarla (minúsculas, orden de listas…). */
    public function prepararFila(string $hoja, array $f): array
    {
        return $f;
    }

    /** Ajusta las filas de una hoja leída antes de analizarlas (numerar tramos…). */
    public function prepararHoja(string $hoja, array $filas): array
    {
        return $filas;
    }

    /** Avisos generales de la lectura (ej. contratos que no se leen por la vigencia). Se piden después de prepararHoja(). @return string[] */
    public function avisosLectura(): array
    {
        return [];
    }

    /**
     * Errores propios de una fila (además de obligatorios, tipos, listas, referencias y repetidos).
     * @param array $contexto ['matriz' => columnas de la matriz del Excel, 'analisis' => hojas ya analizadas]
     * @return string[]
     */
    public function reglasFila(string $hoja, array $f, array &$avisos, array $contexto): array
    {
        return [];
    }

    /** Cambios que no salen de comparar columnas (ej. "Clave nueva"). @return string[] */
    public function cambiosExtra(string $hoja, array $antes, array $f): array
    {
        return [];
    }

    /** Avisos de una fila nueva (ej. la clave inicial de un usuario). @return string[] */
    public function avisosNueva(string $hoja, array $f): array
    {
        return [];
    }

    /** Guarda una hoja completa a su manera (por grupos); null = fila por fila con guardar{Hoja}(). */
    public function guardarHoja(string $hoja, array $filasAnalizadas): ?int
    {
        return null;
    }

    /** Orden en que se guardan las filas de una hoja (padres antes que hijos). */
    public function ordenarParaGuardar(string $hoja, array $filas): array
    {
        return $filas;
    }

    public function guardar(string $hoja, array $datos): void
    {
        $this->{'guardar' . Str::studly($hoja)}($datos);
    }

    /** Inserta o actualiza por clave (con created_at / updated_at). */
    protected function upsert(string $tabla, array $clave, array $valores, bool $conFechas = true): void
    {
        $existe = \Illuminate\Support\Facades\DB::table($tabla)->where($clave)->exists();
        $fecha = $conFechas ? ['updated_at' => now()] : [];
        $existe ? \Illuminate\Support\Facades\DB::table($tabla)->where($clave)->update($valores + $fecha)
            : \Illuminate\Support\Facades\DB::table($tabla)->insert($clave + $valores + $fecha + ($conFechas ? ['created_at' => now()] : []));
    }

    /** Valores de una lista separada por comas ("a, b"). @return string[] */
    public static function lista(?string $texto): array
    {
        return array_values(array_filter(array_map('trim', explode(',', (string) $texto)), fn($v) => $v !== ''));
    }

    /** Antes y después de guardar todo (en la transacción / fuera de ella). */
    public function despuesDeGuardar(): void
    {
    }

    // -------------------------------------------------------------- matriz (opcional)

    /**
     * Matriz del sistema: ['fijas' => encabezados fijos, 'filas' => [[valores fijos]], 'columnas' => [nombre => [claves marcadas]]].
     * La clave de cada fila es su última columna fija.
     */
    public function matrizSistema(): ?array
    {
        return null;
    }

    /** @param array $excel ['columnas' => string[], 'marcas' => [columna => claves]] */
    public function analizarMatriz(array $excel): ?array
    {
        return null;
    }

    public function guardarMatriz(array $analisis): int
    {
        return 0;
    }

    /** Hoja antes de la cual se guarda la matriz (recursos: roles antes que usuarios). */
    public function matrizAntesDe(): ?string
    {
        return null;
    }
}

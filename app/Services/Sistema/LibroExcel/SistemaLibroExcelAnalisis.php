<?php

namespace App\Services\Sistema\LibroExcel;

/**
 * Vista previa de un libro Excel subido: compara cada fila con el sistema por su identificación (columnas clave de la
 * hoja) y la marca nuevo / cambia / igual / error, con el detalle de los cambios. Valida obligatorios, listas, fechas,
 * números, repetidos y referencias entre hojas (lo referido puede venir en el mismo Excel o existir en el sistema).
 * Además informa qué hay que cargar antes (faltantes) y si el archivo está al día (marca de versión).
 */
class SistemaLibroExcelAnalisis
{
    public function __construct(private SistemaLibroExcel $excel)
    {
    }

    /**
     * @param array $libro resultado de SistemaLibroExcel::leer
     * @param string[]|null $hojas hojas que se pueden importar (null = todas)
     */
    public function analizar(SistemaLibroExcelDefinicion $def, array $libro, ?array $hojas = null): array
    {
        $hojasSubidas = $hojas; // $hojas se reutiliza abajo para el resultado por hoja
        $reglas = $def->reglas();
        $sistema = $this->excel->filasNormalizadas($def);
        $hojasExcel = [];
        foreach ($libro['hojas'] as $hoja => $filas) {
            $hojasExcel[$hoja] = $def->prepararHoja($hoja, $filas);
        }
        $contexto = ['matriz' => $libro['matriz']['columnas'] ?? []];

        // Referencias hoja.columna: lo que ya está en el sistema y lo que trae el Excel (separando las filas con errores).
        // Las hojas referidas van antes en HOJAS, así que ya están analizadas.
        $conjunto = fn(array $valores) => array_fill_keys(array_map(fn($v) => mb_strtolower((string) $v), array_filter($valores, fn($v) => $v !== null)), true);
        $referencias = [];
        $hojas = [];
        $referencia = function (string $ref) use (&$referencias, &$hojas, $sistema, $hojasExcel, $conjunto): array {
            [$hoja, $columna] = explode('.', $ref);
            if (isset($referencias[$ref])) {
                return $referencias[$ref];
            }
            $r = ['sistema' => $conjunto(array_column($sistema[$hoja] ?? [], $columna)), 'excel' => [], 'con_error' => [], 'en_excel' => isset($hojasExcel[$hoja])];
            if (isset($hojas[$hoja])) {
                $filas = collect($hojas[$hoja]['filas']);
                $r['excel'] = $conjunto($filas->where('estado', '!=', 'error')->pluck("datos.{$columna}")->all());
                $r['con_error'] = $conjunto($filas->where('estado', 'error')->pluck("datos.{$columna}")->all());
                return $referencias[$ref] = $r; // solo se guarda cuando la hoja referida ya se analizó
            }
            $r['excel'] = $conjunto(array_column($hojasExcel[$hoja] ?? [], $columna)); // misma hoja (padre)
            return $r;
        };
        $faltantes = [];

        foreach ($reglas::HOJAS as $clave => [$titulo, $identidad, $columnas]) {
            if (!isset($hojasExcel[$clave])) {
                continue;
            }
            $porClave = [];
            foreach ($sistema[$clave] ?? [] as $f) {
                $porClave[$this->clave($f, $identidad)] = $f;
            }
            $vistas = [];
            $unicos = [];
            $filas = [];
            foreach ($hojasExcel[$clave] as $f) {
                $errores = [];
                $avisos = [];
                foreach ($columnas as $c => $d) {
                    if ($f[$c] === null && isset($d['def'])) {
                        $f[$c] = $d['def'];
                    }
                }
                $f = $def->prepararFila($clave, $f);
                foreach ($columnas as $c => $d) {
                    $v = $f[$c];
                    if ($v === null) {
                        if (!empty($d['req'])) {
                            $errores[] = "Falta {$d['t']}.";
                        }
                        continue;
                    }
                    if (in_array($d['tipo'], ['entero', 'decimal'], true) && !is_numeric($v)) {
                        $errores[] = "{$d['t']}: \"{$v}\" no es un número.";
                    } elseif ($d['tipo'] === 'fecha' && !$reglas::esFecha($v)) {
                        $errores[] = "{$d['t']}: \"{$v}\" no es una fecha (dd/mm/aaaa).";
                    } elseif ($d['tipo'] === 'sino' && !in_array($v, ['SI', 'NO'], true)) {
                        $errores[] = "{$d['t']}: escribe SI o NO.";
                    }
                    if (isset($d['op']) && !in_array($v, $reglas::OPCIONES[$d['op']], true)) {
                        $errores[] = "{$d['t']}: \"{$v}\" no es válido (" . implode(', ', $reglas::OPCIONES[$d['op']]) . ').';
                    }
                    if (isset($d['ref'])) {
                        $r = $referencia($d['ref']);
                        $k = mb_strtolower($v);
                        $tituloRef = $reglas::HOJAS[explode('.', $d['ref'])[0]][0];
                        if (isset($r['sistema'][$k]) || isset($r['excel'][$k])) {
                            // existe
                        } elseif (isset($r['con_error'][$k])) {
                            $errores[] = "{$d['t']}: \"{$v}\" tiene errores en la hoja {$tituloRef}; corrígela primero.";
                        } elseif ($r['en_excel']) {
                            $errores[] = "{$d['t']}: \"{$v}\" no existe (ni en la hoja {$tituloRef} ni en el sistema).";
                        } else {
                            // La hoja referida no vino en este archivo: hay que cargarla antes
                            $errores[] = "{$d['t']}: \"{$v}\" aún no existe en el sistema. Carga primero {$tituloRef}.";
                            $faltantes[$tituloRef][$v] = true;
                        }
                    }
                    if (!empty($d['unico'])) {
                        $k = mb_strtolower($v);
                        if (isset($unicos[$c][$k])) {
                            $errores[] = "{$d['t']} repetido con la fila {$unicos[$c][$k]}.";
                        }
                        $unicos[$c][$k] ??= $f['fila'];
                    }
                }
                $errores = array_merge($errores, $def->reglasFila($clave, $f, $avisos, $contexto + ['analisis' => $hojas]));

                $k = $this->clave($f, $identidad);
                if (isset($vistas[$k])) {
                    $errores[] = "Repetido: ya está en la fila {$vistas[$k]}.";
                }
                $vistas[$k] ??= $f['fila'];

                $antes = $porClave[$k] ?? null;
                $cambios = [];
                if ($antes) {
                    foreach ($columnas as $c => $d) {
                        if (!empty($d['lectura']) || !empty($d['secreta'])) {
                            continue;
                        }
                        if ($this->distinto($antes[$c], $f[$c], $d['tipo'])) {
                            $cambios[] = "{$d['t']}: " . ($antes[$c] ?? '(vacío)') . ' → ' . ($f[$c] ?? '(vacío)');
                        }
                    }
                    $cambios = array_merge($cambios, $def->cambiosExtra($clave, $antes, $f));
                } else {
                    $avisos = array_merge($avisos, $def->avisosNueva($clave, $f));
                }
                $estado = $errores ? 'error' : (!$antes ? 'nuevo' : ($cambios ? 'cambia' : 'igual'));
                $filas[] = ['fila' => $f['fila'], 'clave' => $this->etiqueta($f, $identidad), 'estado' => $estado,
                    'cambios' => $cambios, 'errores' => $errores, 'avisos' => $avisos, 'datos' => $f];
            }
            $soloSistema = array_values(array_map(fn($f) => $this->etiqueta($f, $identidad), array_diff_key($porClave, $vistas)));
            $hojas[$clave] = ['titulo' => $titulo, 'filas' => $filas, 'solo_sistema' => $soloSistema, 'resumen' => $this->contar($filas)];
        }

        $matriz = $libro['matriz'] ? $def->analizarMatriz($libro['matriz']) : null;
        if ($matriz) {
            $matriz['titulo'] = $reglas::TITULO_MATRIZ;
            $matriz['resumen'] = $this->contar($matriz['filas']);
        }
        $resumen = [];
        foreach (array_merge(array_column($hojas, 'resumen'), $matriz ? [$matriz['resumen']] : []) as $r) {
            foreach ($r as $estado => $n) {
                $resumen[$estado] = ($resumen[$estado] ?? 0) + $n;
            }
        }

        return ['hojas' => $hojas, 'matriz' => $matriz, 'avisos' => array_merge($libro['avisos'], $def->avisosLectura()), 'resumen' => $resumen,
            // hoja que hay que cargar antes => valores que faltan en el sistema
            'faltantes' => array_map(fn($valores) => array_keys($valores), $faltantes),
            'version' => $this->version($def, $libro['version'] ?? [], $hojasSubidas)];
    }

    /** ¿El archivo se descargó con los datos que hoy tiene el sistema? (firma de las hojas que se descargaron) */
    private function version(SistemaLibroExcelDefinicion $def, array $version, ?array $hojasSubidas = null): array
    {
        $descargado = $version['descargado'] ?? null;
        if (empty($version['firma'])) {
            return ['estado' => 'sin_marca', 'descargado' => $descargado];
        }
        if (!empty($version['sistema']) && $version['sistema'] !== SistemaLibroExcel::idSistema()) {
            // Descargado de otro dominio (sincronizar los dos sistemas): se compara fila por fila con los datos de este
            return ['estado' => 'otro_sistema', 'descargado' => $descargado];
        }
        // Hoja por hoja (la macro actualiza cada hoja por separado): todas las que se suben deben estar al día
        $hojasSubidas ??= array_keys($version['firmas'] ?? []);
        if ($hojasSubidas && !empty($version['firmas']) && !array_diff($hojasSubidas, array_keys($version['firmas']))) {
            foreach ($hojasSubidas as $hoja) {
                if ($this->excel->firma($def, [$hoja]) !== $version['firmas'][$hoja]) {
                    return ['estado' => 'desactualizada', 'descargado' => $descargado];
                }
            }
            return ['estado' => 'al_dia', 'descargado' => $descargado];
        }
        $actual = $this->excel->firma($def, $version['hojas'] ?? null);
        return ['estado' => $actual === $version['firma'] ? 'al_dia' : 'desactualizada', 'descargado' => $descargado];
    }

    public static function lista(?string $texto): array
    {
        return array_values(array_filter(array_map('trim', explode(',', (string) $texto)), fn($v) => $v !== ''));
    }

    private function distinto(?string $a, ?string $b, string $tipo): bool
    {
        if (in_array($tipo, ['entero', 'decimal'], true) && is_numeric($a) && is_numeric($b)) {
            return abs((float) $a - (float) $b) > 0.000001;
        }
        return (string) $a !== (string) $b;
    }

    private function clave(array $f, array $identidad): string
    {
        return implode('|', array_map(fn($c) => mb_strtolower((string) ($f[$c] ?? '')), $identidad));
    }

    private function etiqueta(array $f, array $identidad): string
    {
        return implode(' · ', array_map(fn($c) => $c === 'n' ? '#' . $f[$c] : ($f[$c] ?? ''), $identidad));
    }

    private function contar(array $filas): array
    {
        $r = [];
        foreach ($filas as $f) {
            $r[$f['estado']] = ($r[$f['estado']] ?? 0) + 1;
        }
        return $r;
    }
}

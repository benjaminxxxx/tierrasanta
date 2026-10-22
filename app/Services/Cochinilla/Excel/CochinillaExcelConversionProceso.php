<?php

namespace App\Services\Cochinilla\Excel;

use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\IReadFilter;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * Conversión única del Excel histórico de cochinilla (COCHINILLA 2026 al ….xlsx) al formato oficial, un archivo por año.
 * Lee cada hoja con los valores que Excel ya calculó (sin recalcular sus fórmulas pesadas) y reparte los datos por año
 * (ver CochinillaExcelReglas). Devuelve los archivos generados y los problemas encontrados (no corrige nada: los datos
 * pasan tal cual para que se revisen en el Excel nuevo o al importarlo).
 *
 * Columnas del Excel antiguo (índice desde 0):
 *  ingreso:     A lote, B sublote, C fecha, H campo, R total kilos, S observación
 *  Venteado:    A lote, C fecha proceso, F kilos ingresados, G limpia, I polvillo (fila 16 en adelante)
 *  Filtrado:    A lote, C fecha proceso, F kilos ingresados, G primera, H segunda, I tercera, J piedra (fila 13…)
 *  Infestacion: B fecha infestación, C fecha reinfestación, D campo, G kg madres, I campo origen; envases e
 *               infestadores por método: cartones (J,K)(N,O)(R,S)(V,W)(Z,AA)(AD,AE), tubos (AH,AI), mallas (AL,AM)
 */
class CochinillaExcelConversionProceso
{
    private const GRUPOS_INFESTACION = [
        [9, 10, 'carton'], [13, 14, 'carton'], [17, 18, 'carton'], [21, 22, 'carton'], [25, 26, 'carton'], [29, 30, 'carton'],
        [33, 34, 'tubo'], [37, 38, 'malla'],
    ];

    public function __construct(private CochinillaExcelLibro $libro, private CochinillaExcelCatalogoConsulta $catalogo)
    {
    }

    /**
     * @return array{archivos: array<int, string>, problemas: array<int, string>, resumen: array<int, array>}
     */
    public function convertir(string $ruta, string $carpeta = 'cochinilla/convertidos'): array
    {
        $problemas = [];
        $porAnio = [];
        $observaciones = $this->catalogo->observaciones(); // codigo => descripción

        // --- Ingreso
        $ingreso = $this->hoja($ruta, 'ingreso ', 19, 11);
        $anioLote = [];
        $grupos = []; // lote => filas en orden (sublotes + total)
        $pendientes = [];
        $anioPrevio = null;
        $sublotesVistos = [];
        foreach ($ingreso as $n => $v) {
            // Filas preparadas con fórmulas y sin datos (al final de la hoja)
            if (trim((string) $v[0]) === '' && trim((string) $v[1]) === '' && !is_numeric($v[17])) {
                continue;
            }
            $fecha = $this->fechaAntigua($v[2], $anioPrevio, "Ingreso fila {$n}", $problemas);
            $obsCodigo = CochinillaExcelReglas::codigoObservacion($v[18]);
            $fila = [
                'lote' => null, 'sublote' => null, 'fecha' => $fecha, 'campo' => $this->catalogo->campo($this->texto($v[7])),
                'observacion' => $observaciones[$obsCodigo] ?? $this->texto($v[18]), 'kilos' => CochinillaExcelReglas::kilos($v[17]),
            ];
            if (trim((string) $v[0]) !== '') {
                $lote = CochinillaExcelReglas::lote($v[0]);
                if ($lote === null) {
                    $problemas[] = "Ingreso fila {$n}: lote \"{$v[0]}\" no es un número.";
                    continue;
                }
                $fila['lote'] = $lote;
                // Lote sin fecha: la del último sublote (lote a medio registrar)
                if (!$fila['fecha']) {
                    $fila['fecha'] = collect($pendientes)->pluck('fecha')->filter()->max();
                    $fecha = $fila['fecha'];
                }
                // Solo los sublotes de este lote (1457.x) van con él; los demás no tienen fila de lote
                $propios = array_values(array_filter($pendientes, fn($p) => CochinillaExcelReglas::loteDeSublote($p['sublote']) === $lote));
                $huerfanos = array_filter($pendientes, fn($p) => CochinillaExcelReglas::loteDeSublote($p['sublote']) !== $lote);
                if ($huerfanos) {
                    $problemas[] = 'Sublote(s) ' . implode(', ', array_column($huerfanos, 'sublote')) . " sin fila de su lote (antes del lote {$lote}): no se incluyen.";
                }
                $grupos[$lote] = array_merge($propios, [$fila]);
                $pendientes = [];
                $anioLote[$lote] = $fecha ? (int) substr($fecha, 0, 4) : ($anioPrevio ?? null);
                $anioPrevio = $anioLote[$lote] ?? $anioPrevio;
                $sumaSub = array_sum(array_column(array_filter($grupos[$lote], fn($r) => $r['lote'] === null), 'kilos'));
                if (count($grupos[$lote]) > 1 && abs($sumaSub - (float) $fila['kilos']) > 0.05) {
                    $problemas[] = "Lote {$lote}: el total ({$fila['kilos']}) no cuadra con la suma de sus sublotes ({$sumaSub}). En el archivo nuevo el total pasa a ser la suma.";
                }
            } elseif (trim((string) $v[1]) !== '') {
                $sub = is_float($v[1]) || is_int($v[1]) ? rtrim(rtrim(number_format((float) $v[1], 4, '.', ''), '0'), '.') : (string) $v[1];
                $fila['sublote'] = CochinillaExcelReglas::sublote($sub) ?? $sub;
                if (isset($sublotesVistos[$fila['sublote']])) {
                    $problemas[] = "Sublote {$fila['sublote']} repetido (filas {$sublotesVistos[$fila['sublote']]} y {$n}). En Excel 1457.10 se guardaba como 1457.1: revísalo.";
                }
                $sublotesVistos[$fila['sublote']] = $n;
                if ($fila['kilos'] === null) {
                    $problemas[] = "Sublote {$fila['sublote']} (fila {$n}) sin kilos: complétalo en el Excel nuevo.";
                }
                $pendientes[] = $fila;
                $anioPrevio = $fecha ? (int) substr($fecha, 0, 4) : $anioPrevio;
            }
        }
        if ($pendientes) {
            $problemas[] = count($pendientes) . ' sublote(s) al final de la hoja de ingreso sin fila de lote: ' . implode(', ', array_column($pendientes, 'sublote'));
        }
        foreach ($grupos as $lote => $filas) {
            $anio = $anioLote[$lote] ?? null;
            if (!$anio) {
                $problemas[] = "Lote {$lote}: no se pudo saber el año (sin fechas): no se incluye.";
                continue;
            }
            foreach ($filas as $f) {
                $f['campania'] = $this->catalogo->campania($f['campo'], $f['fecha']);
                $porAnio[$anio]['ingreso'][] = $f;
            }
        }

        // --- Venteado y Filtrado: al año de su lote (o de su fecha de proceso si el lote no está en el ingreso)
        foreach ([['Venteado', 10, 16, 'venteado'], ['Filtrado', 11, 13, 'filtrado']] as [$hoja, $cols, $desde, $clave]) {
            $anioPrevio = null;
            foreach ($this->hoja($ruta, $hoja, $cols, $desde) as $n => $v) {
                $lote = CochinillaExcelReglas::lote($v[0]);
                $fecha = $this->fechaAntigua($v[2], $anioLote[$lote] ?? $anioPrevio, "{$hoja} fila {$n}", $problemas);
                if ($lote === null) {
                    if (array_filter(array_slice($v, 5), fn($x) => is_numeric($x) && $x != 0)) {
                        $problemas[] = "{$hoja} fila {$n}: sin lote válido (\"{$v[0]}\"): no se incluye.";
                    }
                    continue;
                }
                $anio = $anioLote[$lote] ?? ($fecha ? (int) substr($fecha, 0, 4) : $anioPrevio);
                if (!$anio) {
                    $problemas[] = "{$hoja} fila {$n}: lote {$lote} sin año (sin ingreso ni fecha): no se incluye.";
                    continue;
                }
                $anioPrevio = $anio;
                $porAnio[$anio][$clave][] = $clave === 'venteado'
                    ? ['lote' => $lote, 'fecha' => $fecha, 'kilos' => CochinillaExcelReglas::kilos($v[5]), 'limpia' => CochinillaExcelReglas::kilos($v[6]),
                        'basura' => null, 'polvillo' => CochinillaExcelReglas::kilos($v[8])]
                    : ['lote' => $lote, 'fecha' => $fecha, 'kilos' => CochinillaExcelReglas::kilos($v[5]), 'primera' => CochinillaExcelReglas::kilos($v[6]),
                        'segunda' => CochinillaExcelReglas::kilos($v[7]), 'tercera' => CochinillaExcelReglas::kilos($v[8]), 'piedra' => CochinillaExcelReglas::kilos($v[9]), 'basura' => null];
            }
        }

        // --- Infestación
        foreach ($this->hoja($ruta, 'Infestacion', 43, 15) as $n => $v) {
            $fInf = $this->libro->fecha($v[1]);
            $fReinf = $this->libro->fecha($v[2]);
            $fecha = $fInf && !str_starts_with($fInf, 'invalida') ? $fInf : ($fReinf && !str_starts_with($fReinf, 'invalida') ? $fReinf : null);
            $campo = $this->catalogo->campo($this->texto($v[3]));
            if (!$fecha || !$campo) {
                continue; // filas de totales / vacías
            }
            // Formato Propuesta 2: una fila por método (si la fila antigua usó dos, se parte y los kg se reparten por infestadores)
            $metodos = [];
            foreach (self::GRUPOS_INFESTACION as [$cEnv, $cInf, $m]) {
                if (is_numeric($v[$cEnv] ?? null) && $v[$cEnv] > 0) {
                    $metodos[] = [$m, (float) $v[$cEnv], is_numeric($v[$cInf] ?? null) ? (float) $v[$cInf] : 0.0];
                }
            }
            if (!$metodos) {
                $problemas[] = "Infestación fila {$n} ({$campo} " . $fecha . "): sin envases de cartón, tubo ni malla.";
                $metodos = [[null, null, 0.0]];
            } elseif (count($metodos) > 1) {
                $problemas[] = "Infestación fila {$n} ({$campo} {$fecha}): usó " . implode(' y ', array_column($metodos, 0)) . ': se separó en una fila por método (kg de madres repartidos según infestadores).';
            }
            $kg = CochinillaExcelReglas::kilos($v[6]);
            $totalInf = array_sum(array_column($metodos, 2));
            foreach ($metodos as [$metodo, $envases, $infestadores]) {
                $porAnio[(int) substr($fecha, 0, 4)]['infestacion'][] = [
                    'tipo' => $fInf && !str_starts_with($fInf, 'invalida') ? 'infestacion' : 'reinfestacion',
                    'fecha' => $fecha, 'campo' => $campo, 'area' => $this->catalogo->area($campo), 'campania' => $this->catalogo->campania($campo, $fecha),
                    'kg_madres' => count($metodos) > 1 && $totalInf > 0 ? round($kg * $infestadores / $totalInf, 2) : $kg,
                    'origen' => $this->catalogo->campo($this->texto($v[8])),
                    'metodo' => $metodo, 'envases' => $envases, 'capacidad' => $envases && $infestadores ? round($infestadores / $envases, 2) : null,
                    'malla' => is_numeric($v[41] ?? null) && $v[41] > 0 ? round((float) $v[41], 4) : null,
                    'procedencia' => $this->texto($v[42] ?? null),
                ];
            }
        }

        // --- Hojas que viajan sin importarse
        foreach (CochinillaExcelReglas::HOJAS_EXTRA as $nombre => $spec) {
            $encabezados = $this->encabezados($ruta, $spec['antigua'], $spec['encabezado'], $spec['columnas']);
            // Columnas de fecha: las que en el encabezado dicen fecha / infestación (las demás quedan como número)
            $colsFecha = array_keys(array_filter($encabezados, fn($e) => preg_match('/fech|infest/i', (string) $e)));
            $anioPrevio = null;
            $sinAnio = []; // filas antes de la primera fecha: van al año de la siguiente fila con fecha
            foreach ($this->hoja($ruta, $spec['antigua'], $spec['columnas'], $spec['datos']) as $v) {
                // Fila vacía: solo el número de ítem y ceros (fórmulas de filas preparadas)
                if (!array_filter(array_slice($v, 1), fn($x) => $x !== null && $x !== '' && !(is_numeric($x) && $x == 0))) {
                    continue;
                }
                $anio = null;
                foreach ($colsFecha as $i) {
                    if (is_numeric($v[$i] ?? null) && $v[$i] > 36526 && $v[$i] < 60000) {
                        $f = $this->libro->fecha($v[$i]);
                        $v[$i] = ['fecha' => $f];
                        $anio ??= (int) substr($f, 0, 4);
                    }
                }
                $v = array_map(fn($x) => is_string($x) && str_starts_with($x, '#') ? null : $x, $v);
                $anio ??= $anioPrevio;
                if (!$anio) {
                    $sinAnio[] = $v;
                    continue;
                }
                $anioPrevio = $anio;
                $porAnio[$anio]['extras'][$nombre]['encabezados'] = $encabezados;
                foreach ($sinAnio as $p) {
                    $porAnio[$anio]['extras'][$nombre]['filas'][] = $p;
                }
                $sinAnio = [];
                $porAnio[$anio]['extras'][$nombre]['filas'][] = $v;
            }
        }

        // --- Archivos
        ksort($porAnio);
        $archivos = [];
        $resumen = [];
        Storage::disk('local')->makeDirectory($carpeta);
        foreach ($porAnio as $anio => $datos) {
            foreach (array_keys(CochinillaExcelReglas::HOJAS_EXTRA) as $nombre) {
                $datos['extras'][$nombre] ??= ['encabezados' => $this->encabezados($ruta, CochinillaExcelReglas::HOJAS_EXTRA[$nombre]['antigua'],
                    CochinillaExcelReglas::HOJAS_EXTRA[$nombre]['encabezado'], CochinillaExcelReglas::HOJAS_EXTRA[$nombre]['columnas']), 'filas' => []];
            }
            $ruta_ = "{$carpeta}/" . CochinillaExcelReglas::nombreArchivo($anio);
            (new Xlsx($this->libro->crear($anio, $datos, $this->catalogo->listas())))->save(Storage::disk('local')->path($ruta_));
            $archivos[$anio] = $ruta_;
            $resumen[$anio] = [
                'lotes' => count(array_filter($datos['ingreso'] ?? [], fn($r) => $r['lote'] !== null)),
                'sublotes' => count(array_filter($datos['ingreso'] ?? [], fn($r) => $r['lote'] === null)),
                'venteado' => count($datos['venteado'] ?? []),
                'filtrado' => count($datos['filtrado'] ?? []),
                'infestacion' => count($datos['infestacion'] ?? []),
                'extras' => array_map(fn($e) => count($e['filas']), $datos['extras']),
            ];
        }
        return compact('archivos', 'problemas', 'resumen');
    }

    // ------------------------------------------------------------------ lectura del Excel antiguo

    /** @var array<string, array<int, array>>|null hoja => fila => valores (todas las hojas, leídas una sola vez) */
    private ?array $hojas = null;

    /** Columnas que se leen de cada hoja antigua (el resto, como las 16 141 columnas de formato de "ingreso", se ignora). */
    private function columnasPorHoja(): array
    {
        $cols = ['ingreso ' => 19, 'Venteado' => 10, 'Filtrado' => 11, 'Infestacion' => 43];
        foreach (CochinillaExcelReglas::HOJAS_EXTRA as $spec) {
            $cols[$spec['antigua']] = $spec['columnas'];
        }
        return $cols;
    }

    /**
     * Abre el Excel antiguo UNA vez con todas las hojas necesarias (cada una limitada a sus columnas) y guarda sus
     * valores ya calculados por Excel. Abrirlo hoja por hoja tardaba más de media hora.
     */
    private function cargar(string $ruta): void
    {
        if ($this->hojas !== null) {
            return;
        }
        // Lector en streaming: PhpSpreadsheet tardaba más de 10 minutos con este libro (formato en 16 141 columnas)
        $lector = new \App\Support\XlsxLectorStream($ruta);
        $this->hojas = [];
        foreach ($this->columnasPorHoja() as $nombre => $columnas) {
            $this->hojas[$nombre] = $lector->filas($nombre, $columnas);
        }
    }

    /** Filas con datos de una hoja antigua desde la fila $desde. @return array<int, array> fila => valores */
    private function hoja(string $ruta, string $nombre, int $columnas, int $desde): array
    {
        $this->cargar($ruta);
        return array_filter($this->hojas[$nombre] ?? [], fn($n) => $n >= $desde, ARRAY_FILTER_USE_KEY);
    }

    /** Encabezado de una hoja antigua uniendo sus filas de títulos ("PRIMERA Ingreso"). */
    private function encabezados(string $ruta, string $nombre, array $filas, int $columnas): array
    {
        $datos = $this->hoja($ruta, $nombre, $columnas, min($filas));
        $enc = [];
        $ultimo = '';
        for ($c = 0; $c < $columnas; $c++) {
            $partes = [];
            foreach ($filas as $f) {
                $t = trim(str_replace("\n", ' ', (string) ($datos[$f][$c] ?? '')));
                if ($t !== '' && !str_starts_with($t, '=')) {
                    $partes[] = $t;
                }
            }
            // Títulos agrupados (PRIMERA sobre Ingreso/Salida/Saldo): se repite el grupo de la izquierda
            if (count($partes) === 1 && count($filas) > 1 && $ultimo && in_array($partes[0], ['Ingreso', 'Salida', 'Saldo', 'Serie', 'N°', 'Nº', 'Cant', 'Costo', 'KILOS', '%'], true)) {
                $partes = [$ultimo, $partes[0]];
            } elseif (count($partes) > 1) {
                $ultimo = $partes[0];
            }
            $enc[] = implode(' ', $partes) ?: Coordinate::stringFromColumnIndex($c + 1);
        }
        return $enc;
    }

    /**
     * Fecha del Excel antiguo. Algunas se escribieron como "24.4" (día.mes sin año): Excel las guardó como el número
     * 24.4 (= 24/01/1900). Se interpretan como día/mes del año del contexto (fila anterior o lote) y se avisa.
     */
    private function fechaAntigua($v, ?int $anioContexto, string $donde, array &$problemas): ?string
    {
        if ($v === null || $v === '') {
            return null;
        }
        if (is_numeric($v) && $v >= 40179) { // desde 2010
            return $this->libro->fecha($v);
        }
        if (is_numeric($v) && $v > 0 && $v < 1000 && $anioContexto) {
            [$d, $m] = array_pad(explode('.', (string) $v), 2, null);
            if ($m !== null && checkdate((int) $m, (int) $d, $anioContexto)) {
                $f = sprintf('%04d-%02d-%02d', $anioContexto, (int) $m, (int) $d);
                $problemas[] = "{$donde}: fecha escrita \"{$v}\" (sin año) → se tomó " . date('d/m/Y', strtotime($f)) . '. Revísala.';
                return $f;
            }
        }
        if (!is_numeric($v)) {
            $f = $this->libro->fecha($v);
            if ($f && !str_starts_with($f, 'invalida') && (int) substr($f, 0, 4) >= 2010) {
                return $f;
            }
        }
        $problemas[] = "{$donde}: fecha \"{$v}\" no válida.";
        return null;
    }

    private function texto($v): ?string
    {
        if ($v === null) {
            return null;
        }
        if (is_float($v) && floor($v) == $v) {
            $v = (string) (int) $v;
        }
        $v = trim((string) $v);
        return $v === '' ? null : $v;
    }
}

<?php

namespace App\Services\Cochinilla\Excel;

use Illuminate\Support\Facades\DB;

/**
 * Vista previa de la importación del Excel oficial de un año (no escribe). Compara con el sistema:
 * - Ingreso por lote: nuevo / sin cambios / con cambios (qué cambia) / con errores; lotes del año que solo están en el
 *   sistema se listan (no se borran).
 * - Venteado y filtrado por lote; infestación por campo + fecha: el grupo del Excel reemplaza al del sistema.
 * - Las hojas que no se importan solo se cuentan para mostrarlas.
 */
class CochinillaExcelImportacionConsulta
{
    public function __construct(private CochinillaExcelCatalogoConsulta $catalogo)
    {
    }

    public function analizar(array $datos, int $anio): array
    {
        // Campos escritos con un alias ("Naranjos B") → nombre oficial
        foreach ($datos['ingreso'] as &$f) { $f['campo'] = $this->catalogo->campo($f['campo']); }
        foreach ($datos['infestacion'] as &$f) { $f['campo'] = $this->catalogo->campo($f['campo']); $f['origen'] = $this->catalogo->campo($f['origen']); }
        unset($f);
        $campos = array_flip($this->catalogo->campos());
        $obsPorTexto = [];
        foreach ($this->catalogo->observaciones() as $codigo => $desc) {
            $obsPorTexto[CochinillaExcelReglas::codigoObservacion($desc)] = $codigo;
            $obsPorTexto[$codigo] = $codigo;
        }
        $ingreso = $this->ingreso($datos['ingreso'], $anio, $campos, $obsPorTexto);
        $lotesExcel = array_column($ingreso['lotes'], 'lote');
        $venteado = $this->procesos($datos['venteado'], 'cochinilla_venteados', $anio, $lotesExcel, ['limpia', 'polvillo'],
            fn($r) => [$r->fecha_proceso ? substr($r->fecha_proceso, 0, 10) : null, round((float) $r->kilos_ingresado, 2), round((float) $r->limpia, 2), round((float) $r->polvillo, 2)]);
        $filtrado = $this->procesos($datos['filtrado'], 'cochinilla_filtrados', $anio, $lotesExcel, ['primera', 'segunda', 'tercera', 'piedra'],
            fn($r) => [$r->fecha_proceso ? substr($r->fecha_proceso, 0, 10) : null, round((float) $r->kilos_ingresados, 2), round((float) $r->primera, 2),
                round((float) $r->segunda, 2), round((float) $r->tercera, 2), round((float) $r->piedra, 2)]);
        $infestacion = $this->infestacion($datos['infestacion'], $anio, $campos);

        $contar = fn(array $grupos) => collect($grupos)->countBy('estado')->all();
        return [
            'anio' => $anio,
            'ingreso' => $ingreso,
            'venteado' => $venteado,
            'filtrado' => $filtrado,
            'infestacion' => $infestacion,
            'extras' => collect($datos['extras'] ?? [])->map(fn($e) => ['encabezados' => $e['encabezados'], 'filas' => count($e['filas']),
                'muestra' => array_slice($e['filas'], 0, 5)])->all(),
            'resumen' => [
                'ingreso' => $contar($ingreso['lotes']),
                'venteado' => $contar($venteado['grupos']),
                'filtrado' => $contar($filtrado['grupos']),
                'infestacion' => $contar($infestacion['grupos']),
            ],
        ];
    }

    // ------------------------------------------------------------------ ingreso

    private function ingreso(array $filas, int $anio, array $campos, array $obsPorTexto): array
    {
        $lotes = [];
        $pendientes = [];
        $sueltos = [];
        $vistosSub = [];
        foreach ($filas as $f) {
            $lote = CochinillaExcelReglas::lote($f['lote']);
            if ($f['lote'] !== null && $f['lote'] !== '') {
                if ($lote === null) {
                    $sueltos[] = "Fila {$f['fila']}: lote \"{$f['lote']}\" no es un número.";
                    continue;
                }
                $propios = array_values(array_filter($pendientes, fn($p) => $p['lote_de'] === $lote));
                foreach (array_filter($pendientes, fn($p) => $p['lote_de'] !== $lote) as $p) {
                    $sueltos[] = "Fila {$p['fila']}: sublote {$p['codigo']} no tiene su fila de lote ({$p['lote_de']}).";
                }
                $pendientes = [];
                $lotes[] = $this->armarLote($lote, $f, $propios, $anio, $campos, $obsPorTexto, isset($lotes[$lote]));
                continue;
            }
            $codigo = CochinillaExcelReglas::sublote($f['sublote']);
            if (!$codigo) {
                $sueltos[] = "Fila {$f['fila']}: falta el lote o el sublote (ej. 1457 o 1457.1).";
                continue;
            }
            if (isset($vistosSub[$codigo])) {
                $sueltos[] = "Fila {$f['fila']}: sublote {$codigo} repetido (también en la fila {$vistosSub[$codigo]}).";
            }
            $vistosSub[$codigo] = $f['fila'];
            $pendientes[] = ['fila' => $f['fila'], 'codigo' => $codigo, 'lote_de' => CochinillaExcelReglas::loteDeSublote($codigo), 'fecha' => $f['fecha'],
                'campo' => $f['campo'], 'observacion' => $f['observacion'], 'kilos' => CochinillaExcelReglas::kilos($f['kilos'])];
        }
        foreach ($pendientes as $p) {
            $sueltos[] = "Fila {$p['fila']}: sublote {$p['codigo']} sin fila de lote al final (escribe la fila del lote {$p['lote_de']} con su total).";
        }

        // Lotes repetidos en el Excel
        $conteo = array_count_values(array_column($lotes, 'lote'));
        foreach ($lotes as &$l) {
            if ($conteo[$l['lote']] > 1) {
                $l['errores'][] = 'El lote aparece más de una vez en el Excel.';
            }
        }
        unset($l);

        $this->compararIngresos($lotes, $anio);
        $enExcel = array_column($lotes, 'lote');
        $soloSistema = DB::table('cochinilla_ingresos')->whereYear('fecha', $anio)->whereNotIn('lote', $enExcel ?: [0])
            ->orderBy('lote')->get(['lote', 'fecha', 'campo', 'total_kilos'])->map(fn($i) => (array) $i)->all();

        return ['lotes' => $lotes, 'errores_sueltos' => $sueltos, 'solo_sistema' => $soloSistema];
    }

    private function armarLote(int $lote, array $f, array $subs, int $anio, array $campos, array $obsPorTexto, bool $repetido): array
    {
        $errores = [];
        $avisos = [];
        $obs = $obsPorTexto[CochinillaExcelReglas::codigoObservacion($f['observacion'])] ?? null;
        $fecha = $f['fecha'] && !str_starts_with($f['fecha'], 'invalida') ? $f['fecha'] : null;
        if (!$fecha) {
            $errores[] = 'Fecha del lote inválida.';
        } elseif ((int) substr($fecha, 0, 4) !== $anio) {
            $errores[] = 'El lote es de ' . substr($fecha, 0, 4) . ': va en el archivo de ese año.';
        }
        if (!$f['campo'] || !isset($campos[$f['campo']])) {
            $errores[] = 'Campo "' . ($f['campo'] ?? '') . '" no existe.';
        }
        if (!$obs) {
            $errores[] = 'Observación "' . ($f['observacion'] ?? '') . '" no reconocida (ver hoja Listas).';
        }
        $total = CochinillaExcelReglas::kilos($f['kilos']);

        // Sin sublotes: el sistema guarda el lote con un sublote N.1 (misma estructura para todos)
        if (!$subs) {
            $subs = [['fila' => $f['fila'], 'codigo' => "{$lote}.1", 'fecha' => $f['fecha'], 'observacion' => $f['observacion'], 'kilos' => $total]];
        }
        $sublotes = [];
        foreach ($subs as $s) {
            $sObs = $obsPorTexto[CochinillaExcelReglas::codigoObservacion($s['observacion'] ?: $f['observacion'])] ?? null;
            $sFecha = $s['fecha'] && !str_starts_with($s['fecha'], 'invalida') ? $s['fecha'] : null;
            if (!$sFecha) {
                $errores[] = "Sublote {$s['codigo']}: fecha inválida.";
            }
            if (!$sObs) {
                $errores[] = "Sublote {$s['codigo']}: observación no reconocida.";
            }
            if (!$s['kilos'] || $s['kilos'] <= 0) {
                $errores[] = "Sublote {$s['codigo']}: kilos inválidos.";
            }
            if (!empty($s['campo']) && $f['campo'] && $s['campo'] !== $f['campo']) {
                $avisos[] = "Sublote {$s['codigo']} con campo {$s['campo']}: se guarda con el campo del lote ({$f['campo']}).";
            }
            $sublotes[$s['codigo']] = ['fecha' => $sFecha, 'kilos' => (float) $s['kilos'], 'observacion' => $sObs];
        }
        $suma = round(array_sum(array_column($sublotes, 'kilos')), 2);
        if ($total !== null && abs($suma - $total) > 0.05) {
            $errores[] = "El total del lote ({$total}) no cuadra con la suma de sus sublotes ({$suma}).";
        }

        return [
            'lote' => $lote, 'fila' => $f['fila'], 'fecha' => $fecha, 'campo' => $f['campo'], 'observacion' => $obs,
            'observacion_texto' => $f['observacion'], 'total' => $suma, 'sublotes' => $sublotes,
            'estado' => null, 'cambios' => [], 'errores' => $errores, 'avisos' => $avisos,
        ];
    }

    private function compararIngresos(array &$lotes, int $anio): void
    {
        $sistema = DB::table('cochinilla_ingresos')->whereIn('lote', array_column($lotes, 'lote') ?: [0])->get()->keyBy('lote');
        $detalles = DB::table('cochinilla_ingreso_detalles')->whereIn('cochinilla_ingreso_id', $sistema->pluck('id'))->get()->groupBy('cochinilla_ingreso_id');
        foreach ($lotes as &$l) {
            $s = $sistema->get($l['lote']);
            if (!$s) {
                $l['estado'] = $l['errores'] ? 'error' : 'nuevo';
                continue;
            }
            $l['ingreso_id'] = $s->id;
            if ((int) substr($s->fecha, 0, 4) !== $anio && $l['fecha']) {
                $l['errores'][] = 'Ese número de lote ya existe en el sistema con fecha ' . substr($s->fecha, 0, 10) . ' (otro año).';
            }
            $cambios = [];
            foreach (['fecha' => 'fecha', 'campo' => 'campo', 'observacion' => 'observación'] as $campo => $etiqueta) {
                $antes = $campo === 'fecha' ? substr((string) $s->fecha, 0, 10) : (string) $s->$campo;
                if ((string) $l[$campo] !== $antes) {
                    $cambios[] = "{$etiqueta}: {$antes} → {$l[$campo]}";
                }
            }
            if (abs((float) $s->total_kilos - $l['total']) > 0.009) {
                $cambios[] = 'total: ' . number_format((float) $s->total_kilos, 2) . ' → ' . number_format($l['total'], 2);
            }
            $subSistema = ($detalles[$s->id] ?? collect())->keyBy('sublote_codigo');
            foreach ($l['sublotes'] as $codigo => $x) {
                $d = $subSistema->get($codigo);
                if (!$d) {
                    $cambios[] = "sublote {$codigo} nuevo (" . number_format($x['kilos'], 2) . ' kg)';
                } elseif (abs((float) $d->total_kilos - $x['kilos']) > 0.009 || substr((string) $d->fecha, 0, 10) !== $x['fecha'] || $d->observacion !== $x['observacion']) {
                    $cambios[] = "sublote {$codigo}: " . number_format((float) $d->total_kilos, 2) . ' → ' . number_format($x['kilos'], 2) . ' kg'
                        . (substr((string) $d->fecha, 0, 10) !== $x['fecha'] ? ', fecha ' . substr((string) $d->fecha, 0, 10) . ' → ' . $x['fecha'] : '');
                }
            }
            foreach ($subSistema->keys()->diff(array_keys($l['sublotes'])) as $codigo) {
                $cambios[] = "sublote {$codigo} se quita (no está en el Excel)";
            }
            $l['cambios'] = $cambios;
            $l['estado'] = $l['errores'] ? 'error' : ($cambios ? 'cambia' : 'igual');
        }
        unset($l);
    }

    // ------------------------------------------------------------------ venteado / filtrado

    private function procesos(array $filas, string $tabla, int $anio, array $lotesExcel, array $columnas, callable $firmaSistema): array
    {
        $grupos = [];
        $sueltos = [];
        foreach ($filas as $f) {
            $lote = CochinillaExcelReglas::lote($f['lote']);
            if ($lote === null) {
                $sueltos[] = "Fila {$f['fila']}: lote \"{$f['lote']}\" no válido.";
                continue;
            }
            $fecha = $f['fecha'] && !str_starts_with($f['fecha'], 'invalida') ? $f['fecha'] : null;
            $valores = array_map(fn($c) => CochinillaExcelReglas::kilos($f[$c]) ?? 0.0, $columnas);
            $kilos = CochinillaExcelReglas::kilos($f['kilos']);
            $errores = [];
            if (!$fecha) {
                $errores[] = "fila {$f['fila']}: fecha inválida";
            }
            if (!$kilos || $kilos <= 0) {
                $errores[] = "fila {$f['fila']}: kilos ingresados inválidos";
            } elseif (array_sum($valores) - $kilos > 0.05) {
                $errores[] = "fila {$f['fila']}: lo clasificado (" . array_sum($valores) . ") supera los kilos ingresados ({$kilos})";
            }
            $grupos[$lote]['lote'] = $lote;
            $grupos[$lote]['filas'][] = ['fecha' => $fecha, 'kilos' => $kilos] + array_combine($columnas, $valores);
            $grupos[$lote]['firmas'][] = implode('|', array_merge([$fecha, $kilos], $valores));
            $grupos[$lote]['errores'] = array_merge($grupos[$lote]['errores'] ?? [], $errores);
        }

        $existenIngreso = DB::table('cochinilla_ingresos')->whereIn('lote', array_keys($grupos) ?: [0])->pluck('lote')->flip();
        $sistema = DB::table($tabla)->whereIn('lote', array_keys($grupos) ?: [0])->get()->groupBy('lote');
        foreach ($grupos as $lote => &$g) {
            if (!isset($existenIngreso[$lote]) && !in_array($lote, $lotesExcel, true)) {
                $g['avisos'][] = 'El lote no está en el ingreso (ni del Excel ni del sistema): se guarda suelto.';
            }
            $firmasSistema = ($sistema[$lote] ?? collect())->map(fn($r) => implode('|', $firmaSistema($r)))->sort()->values()->all();
            $firmasExcel = collect($g['firmas'])->sort()->values()->all();
            $g['sistema'] = count($firmasSistema);
            $g['estado'] = $g['errores'] ? 'error' : (!$firmasSistema ? 'nuevo' : ($firmasSistema === $firmasExcel ? 'igual' : 'cambia'));
            unset($g['firmas']);
        }
        unset($g);

        // Lotes del año con procesos en el sistema que no vienen en el Excel (no se tocan)
        $lotesAnio = DB::table('cochinilla_ingresos')->whereYear('fecha', $anio)->pluck('lote')->all();
        $soloSistema = DB::table($tabla)->whereIn('lote', $lotesAnio ?: [0])->whereNotIn('lote', array_keys($grupos) ?: [0])
            ->selectRaw('lote, COUNT(*) n')->groupBy('lote')->pluck('n', 'lote')->all();

        return ['grupos' => array_values($grupos), 'errores_sueltos' => $sueltos, 'solo_sistema' => $soloSistema];
    }

    // ------------------------------------------------------------------ infestación

    private function infestacion(array $filas, int $anio, array $campos): array
    {
        $grupos = [];
        foreach ($filas as $f) {
            $fecha = $f['fecha'] && !str_starts_with($f['fecha'], 'invalida') ? $f['fecha'] : null;
            $clave = ($fecha ?? 'sin-fecha') . '|' . ($f['campo'] ?? '');
            $errores = [];
            if (!$fecha) {
                $errores[] = "fila {$f['fila']}: fecha inválida";
            } elseif ((int) substr($fecha, 0, 4) !== $anio) {
                $errores[] = "fila {$f['fila']}: la fecha es de otro año";
            }
            if (!in_array($f['tipo'], CochinillaExcelReglas::TIPOS_INFESTACION, true)) {
                $errores[] = "fila {$f['fila']}: tipo debe ser infestacion o reinfestacion";
            }
            if (!$f['campo'] || !isset($campos[$f['campo']])) {
                $errores[] = "fila {$f['fila']}: campo \"{$f['campo']}\" no existe";
            }
            if ($f['origen'] && !isset($campos[$f['origen']])) {
                $errores[] = "fila {$f['fila']}: campo origen \"{$f['origen']}\" no existe";
            }
            if (!in_array($f['metodo'], CochinillaExcelReglas::METODOS, true)) {
                $errores[] = "fila {$f['fila']}: método debe ser carton, tubo o malla";
            }
            $kg = CochinillaExcelReglas::kilos($f['kg_madres']);
            $envases = CochinillaExcelReglas::kilos($f['envases']);
            $capacidad = CochinillaExcelReglas::kilos($f['capacidad']);
            if (!$kg || $kg <= 0) {
                $errores[] = "fila {$f['fila']}: kg de madres inválido";
            }
            if (!$envases || $envases <= 0 || !$capacidad || $capacidad <= 0) {
                $errores[] = "fila {$f['fila']}: envases e infestadores por envase deben ser mayores a 0";
            }
            $fila = ['tipo' => $f['tipo'], 'fecha' => $fecha, 'campo' => $f['campo'], 'kg_madres' => $kg, 'origen' => $f['origen'],
                'metodo' => $f['metodo'], 'envases' => $envases, 'capacidad' => $capacidad,
                'malla' => isset($f['malla']) ? round((float) $f['malla'], 2) : null, 'procedencia' => $f['procedencia'] ?? null];
            $grupos[$clave]['fecha'] = $fecha;
            $grupos[$clave]['campo'] = $f['campo'];
            $grupos[$clave]['filas'][] = $fila;
            $grupos[$clave]['errores'] = array_merge($grupos[$clave]['errores'] ?? [], $errores);
        }

        $sistema = DB::table('cochinilla_infestaciones')->whereYear('fecha', $anio)->get()
            ->groupBy(fn($r) => substr($r->fecha, 0, 10) . '|' . $r->campo_nombre);
        $firma = fn($r) => implode('|', [$r['tipo'], $r['kg_madres'], $r['origen'], $r['metodo'], $r['envases'], $r['capacidad'],
            $r['malla'] !== null ? round((float) $r['malla'], 2) : '', (string) $r['procedencia']]);
        foreach ($grupos as $clave => &$g) {
            $enSistema = ($sistema[$clave] ?? collect())->map(fn($r) => $firma(['tipo' => $r->tipo_infestacion, 'kg_madres' => round((float) $r->kg_madres, 2),
                'origen' => $r->campo_origen_nombre, 'metodo' => $r->metodo, 'envases' => round((float) $r->numero_envases, 2), 'capacidad' => round((float) $r->capacidad_envase, 2),
                'malla' => $r->porcentaje_malla_rashel, 'procedencia' => $r->procedencia_infestadores]))
                ->sort()->values()->all();
            $enExcel = collect($g['filas'])->map($firma)->sort()->values()->all();
            $g['sistema'] = count($enSistema);
            $g['estado'] = $g['errores'] ? 'error' : (!$enSistema ? 'nuevo' : ($enSistema === $enExcel ? 'igual' : 'cambia'));
        }
        unset($g);
        $soloSistema = $sistema->keys()->diff(array_keys($grupos))->map(fn($k) => ['clave' => $k, 'filas' => $sistema[$k]->count()])->values()->all();

        return ['grupos' => array_values($grupos), 'solo_sistema' => $soloSistema];
    }
}

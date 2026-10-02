<?php

namespace App\Services\Caja\Importacion;

use App\Models\CajaArqueo;
use App\Models\CajaArqueoDetalle;
use App\Models\CajaClasificador;
use App\Models\CajaFuente;
use App\Models\CajaMovimiento;
use App\Models\CajaTipoCambio;
use App\Models\Empresa;
use App\Services\Caja\Cierre\CajaCierreConsulta;
use App\Services\Caja\Movimiento\CajaMovimientoReglas;
use App\Services\Reporte\AuditoriaServicio;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\IReadFilter;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Importa el Excel de caja ("Bancos TSH"): hoja BASE (movimientos), Valida (clasificadores), Tipo de Cambio
 * y las notas de saldos por fuente (columnas AB/AE) como arqueos.
 *
 * Columnas de BASE: A empresa, B N° caja, C condición, D categoría, E código, F beneficiario, G gastos B+N,
 * H/I clasificador 1/2, J/K sub-grupo NG/BL, L moneda, M fecha, N semana, O T. Doc, P N° Doc,
 * Q situación cheque, R importe $, S importe S/, T disponible, AA TC. Las demás son fórmulas que el sistema
 * calcula.
 *
 * Reemplaza los movimientos del rango de fechas del archivo (si se confirma y ningún mes está cerrado).
 */
class CajaImportacionExcel
{
    private const HOJA_BASE = 'BASE';
    private const HOJA_VALIDA = 'Valida';
    private const HOJA_TC = 'Tipo de Cambio';

    /** Fuentes de las notas "Saldo AQP S/ … Naranja S/ … Aqp-Flavia S/ … y CUADRILLAS S/ …" (en ese orden). */
    private const FUENTES = ['AQP', 'Naranja', 'Aqp-Flavia', 'Cuadrillas', 'Caja Bancos'];

    /** Agrega solo las filas del Excel que aún no están en el sistema; no borra nada. */
    public const MODO_DIFERENCIA = 'diferencia';
    /** Borra y vuelve a cargar los meses abiertos del archivo. */
    public const MODO_REEMPLAZAR = 'reemplazar';

    /**
     * Se procesa mes por mes: los meses cerrados nunca se tocan (se informan como omitidos).
     *
     * - diferencia: una fila se reconoce por fecha + importe + beneficiario + descripción (contando repetidas). Se
     *   agregan las que faltan; las que están en el sistema y no en el Excel (registradas a mano o corregidas) se
     *   dejan y se informan. Subir el mismo Excel varias veces no duplica nada.
     * - reemplazar: los meses abiertos del archivo se borran y se cargan de nuevo tal como están en el Excel.
     *
     * @return array{movimientos:int, existentes:int, solo_sistema:int, meses_omitidos:string[], clasificadores_nuevos:int,
     *               tipos_cambio:int, arqueos:int, desde:string, hasta:string, saldo_final:float, saldo_final_excel:?float,
     *               semanas_distintas:int, avisos:string[]}
     */
    public function importar(string $ruta, string|bool $modo = self::MODO_DIFERENCIA): array
    {
        @ini_set('memory_limit', '4096M');
        @set_time_limit(600);
        if (is_bool($modo)) {
            $modo = $modo ? self::MODO_REEMPLAZAR : self::MODO_DIFERENCIA;
        }

        $libro = $this->cargar($ruta);
        $base = $libro->getSheetByName(self::HOJA_BASE) ?? throw ValidationException::withMessages(['archivo' => 'El archivo no tiene la hoja BASE.']);

        $todas = $this->leerBase($base);
        if (!$todas) {
            throw ValidationException::withMessages(['archivo' => 'La hoja BASE no tiene movimientos.']);
        }
        $desde = min(array_column($todas, 'fecha'));
        $hasta = max(array_column($todas, 'fecha'));

        // Meses cerrados: fuera
        $cierres = app(CajaCierreConsulta::class);
        $omitidos = [];
        $filas = array_values(array_filter($todas, function ($f) use ($cierres, &$omitidos) {
            $m = substr($f['fecha'], 0, 7);
            if (!array_key_exists($m, $omitidos)) {
                $omitidos[$m] = $cierres->fechaCerrada($f['fecha']);
            }
            return !$omitidos[$m];
        }));
        $mesesOmitidos = array_keys(array_filter($omitidos));
        $mesesAbiertos = array_keys(array_filter($omitidos, fn($cerrado) => !$cerrado));

        $avisos = [];
        return DB::transaction(function () use ($libro, $base, $todas, $filas, $desde, $hasta, $modo, $mesesOmitidos, $mesesAbiertos, &$avisos) {
            $clasificadoresNuevos = $this->importarClasificadores($libro->getSheetByName(self::HOJA_VALIDA), $todas);
            $tipos = $this->importarTiposCambio($libro->getSheetByName(self::HOJA_TC));

            $existentes = 0;
            $soloSistema = 0;
            if ($modo === self::MODO_REEMPLAZAR) {
                foreach ($mesesAbiertos as $mes) {
                    [$ini, $fin] = [Carbon::parse("{$mes}-01")->toDateString(), Carbon::parse("{$mes}-01")->endOfMonth()->toDateString()];
                    CajaMovimiento::withTrashed()->whereBetween('fecha', [$ini, $fin])->forceDelete();
                    CajaArqueo::whereBetween('fecha', [$ini, $fin])->delete();
                }
            } else {
                // Solo lo que falta: se descuentan las filas que ya están (por huella, contando repetidas)
                // Las eliminadas también cuentan: si se borró en el sistema (con motivo), volver a subir el Excel no la revive
                // Solo los meses abiertos del archivo (los cerrados quedaron fuera del Excel leído)
                $enSistema = CajaMovimiento::withTrashed()->whereBetween('fecha', [$desde, $hasta])
                    ->whereIn(DB::raw("DATE_FORMAT(fecha, '%Y-%m')"), $mesesAbiertos ?: ['-'])->get()
                    ->countBy(fn($m) => CajaMovimientoReglas::huella($m->fecha->toDateString(), (float) $m->importe, $m->beneficiario, $m->descripcion))->all();
                $nuevas = [];
                foreach ($filas as $f) {
                    $h = CajaMovimientoReglas::huella($f['fecha'], $f['importe'], $f['beneficiario'], $f['descripcion']);
                    if (($enSistema[$h] ?? 0) > 0) {
                        $enSistema[$h]--;
                        $existentes++;
                    } else {
                        $nuevas[] = $f;
                    }
                }
                $soloSistema = array_sum($enSistema);
                $filas = $nuevas;
            }

            $clasificadores = CajaClasificador::get()->keyBy(fn($c) => $this->clave($c->clasificador_1, $c->clasificador_2));
            $empresa = Empresa::value('razon_social') ?? 'TSH SAC';
            $ahora = now();
            $usuario = auth()->id();
            $semanasDistintas = 0;
            $insertar = [];
            foreach ($filas as $f) {
                $clasificador = $clasificadores->get($this->clave($f['clasificador_1'], $f['clasificador_2']));
                $calculada = CajaMovimientoReglas::semanaDelMes($f['fecha']);
                if ($f['semana'] && $f['semana'] !== $calculada) {
                    $semanasDistintas++;
                }
                // El color del Excel se guarda solo si difiere del que le toca por su clasificador
                $estiloClasif = $clasificador ? ['color_fondo' => $clasificador->color_fondo, 'color_texto' => $clasificador->color_texto, 'negrita' => $clasificador->negrita] : [];
                $fondo = $this->mismoColor($f['color_fondo'], $estiloClasif['color_fondo'] ?? null) ? null : $f['color_fondo'];
                $texto = $this->mismoColor($f['color_texto'], $estiloClasif['color_texto'] ?? null) ? null : $f['color_texto'];

                $insertar[] = [
                    'empresa' => $empresa,
                    'numero_caja' => $f['numero_caja'],
                    'es_contable' => $f['es_contable'],
                    'condicion' => $f['condicion'],
                    'categoria' => $f['categoria'],
                    'codigo' => $f['codigo'],
                    'beneficiario' => $f['beneficiario'],
                    'descripcion' => $f['descripcion'],
                    'caja_clasificador_id' => $clasificador?->id,
                    // El nombre oficial (hoja Valida), aunque en BASE esté escrito distinto
                    'clasificador_1' => $clasificador?->clasificador_1 ?? $f['clasificador_1'],
                    'clasificador_2' => $clasificador?->clasificador_2 ?? $f['clasificador_2'],
                    'subgrupo_ng' => $f['subgrupo_ng'],
                    'subgrupo_bl' => $f['subgrupo_bl'],
                    'moneda' => CajaMovimientoReglas::MONEDA,
                    'fecha' => $f['fecha'],
                    'semana' => $f['semana'] ?: $calculada,
                    'tipo_documento' => $f['tipo_documento'],
                    'numero_documento' => $f['numero_documento'],
                    'situacion_cheque' => $f['situacion_cheque'],
                    'importe_usd' => $f['importe_usd'],
                    'tipo_cambio_operacion' => $f['tipo_cambio_operacion'],
                    'importe' => $f['importe'],
                    'importe_detalle' => $f['importe_detalle'],
                    'es_saldo_inicial' => CajaMovimientoReglas::esSaldoInicial($f['clasificador_1']),
                    'tipo_cambio' => $f['tipo_cambio'],
                    'color_fondo' => $fondo,
                    'color_texto' => $texto,
                    'negrita' => null,
                    'orden' => $f['fila'],
                    'creado_por' => $usuario,
                    'created_at' => $ahora,
                    'updated_at' => $ahora,
                ];
            }
            foreach (array_chunk($insertar, 500) as $lote) {
                CajaMovimiento::insert($lote);
            }

            $arqueos = $this->importarArqueos($base, $todas, $avisos);
            // Las filas que ya están en la caja de oficina quedan vinculadas (al reemplazar se crearon de nuevo)
            app(\App\Services\Caja\Oficina\CajaOficinaProceso::class)->vincularPorHuella();

            $sinClasificador = count(array_filter($insertar, fn($m) => $m['caja_clasificador_id'] === null));
            if ($sinClasificador) {
                $avisos[] = "{$sinClasificador} movimiento(s) sin clasificador 1/2.";
            }

            $ultima = end($todas);
            $resultado = [
                'modo' => $modo,
                'movimientos' => count($insertar),
                'existentes' => $existentes,
                'solo_sistema' => $soloSistema,
                'meses_omitidos' => array_map(fn($m) => Carbon::parse("{$m}-01")->locale('es')->translatedFormat('F Y'), $mesesOmitidos),
                'clasificadores_nuevos' => $clasificadoresNuevos,
                'tipos_cambio' => $tipos,
                'arqueos' => $arqueos,
                'desde' => $desde,
                'hasta' => $hasta,
                'saldo_final' => round((float) CajaMovimiento::where('fecha', '<=', $hasta)->sum('importe'), 2),
                'saldo_final_excel' => $ultima['disponible_excel'],
                'semanas_distintas' => $semanasDistintas,
                'avisos' => $avisos,
            ];
            AuditoriaServicio::registrar(CajaMovimiento::class, 0, 'crear', null, $resultado, 'Importación de caja desde Excel');
            return $resultado;
        });
    }

    /**
     * Las filas de la hoja BASE tal como se leen (para la caja de oficina, que importa el mismo Excel).
     *
     * @return array<int, array<string, mixed>>
     */
    public function leerMovimientos(string $ruta): array
    {
        @ini_set('memory_limit', '4096M');
        @set_time_limit(600);
        $base = $this->cargar($ruta)->getSheetByName(self::HOJA_BASE) ?? throw ValidationException::withMessages(['archivo' => 'El archivo no tiene la hoja BASE.']);
        return $this->leerBase($base);
    }

    private function cargar(string $ruta)
    {
        $reader = IOFactory::createReaderForFile($ruta);
        $reader->setLoadSheetsOnly([self::HOJA_BASE, self::HOJA_VALIDA, self::HOJA_TC]);
        // BASE trae miles de columnas vacías con formato: solo A..AH
        $reader->setReadFilter(new class implements IReadFilter {
            public function readCell($columnAddress, $row, $worksheetName = ''): bool
            {
                return $worksheetName !== 'BASE' || strlen($columnAddress) === 1 || ($columnAddress >= 'AA' && $columnAddress <= 'AH' && strlen($columnAddress) === 2);
            }
        });
        return $reader->load($ruta);
    }

    private function leerBase(Worksheet $ws): array
    {
        $filas = [];
        $ultima = $ws->getHighestDataRow('M');
        for ($r = 3; $r <= $ultima; $r++) {
            $fechaExcel = $ws->getCell("M{$r}")->getCalculatedValue();
            $importe = $this->numero($ws->getCell("S{$r}"));
            if (!is_numeric($fechaExcel) || $importe === null) {
                continue;
            }

            $b = trim((string) $ws->getCell("B{$r}")->getValue());
            $c = mb_strtoupper(trim((string) $ws->getCell("C{$r}")->getValue()));
            $d = trim((string) $ws->getCell("D{$r}")->getCalculatedValue());
            $esContable = strcasecmp($b, 'Contable') === 0 || $c === 'CONTABLE' || strcasecmp($d, 'Contable') === 0;

            $importeUsd = $this->numero($ws->getCell("R{$r}"));
            $o = $ws->getCell("O{$r}")->getValue();
            $tcOperacion = null;
            $tipoDocumento = $this->texto($o);
            if ($importeUsd !== null && is_numeric($o)) {
                $tcOperacion = (float) $o; // en los pagos en dólares, T. Doc lleva el tipo de cambio del pago
                $tipoDocumento = null;
            }

            $sCruda = $ws->getCell("S{$r}")->getValue();
            $detalle = is_string($sCruda) && str_starts_with($sCruda, '=') && $importeUsd === null
                ? mb_substr(ltrim(substr($sCruda, 1), '+'), 0, 255) : null;

            $estilo = $ws->getStyle("F{$r}");
            $fill = $estilo->getFill()->getFillType() !== 'none' ? '#' . strtoupper($estilo->getFill()->getStartColor()->getRGB()) : null;
            $font = '#' . strtoupper($estilo->getFont()->getColor()->getRGB());

            $semana = null;
            if (preg_match('/(\d+)/', (string) $ws->getCell("N{$r}")->getValue(), $m)) {
                $semana = (int) $m[1];
            }

            $filas[] = [
                'fila' => $r,
                'fecha' => Carbon::instance(ExcelDate::excelToDateTimeObject($fechaExcel))->toDateString(),
                'numero_caja' => ctype_digit($b) ? (int) $b : null,
                'es_contable' => $esContable,
                'condicion' => $esContable || $c === 'BLA.' || $c === 'BLA' ? 'BLA' : 'NEG',
                'categoria' => $this->texto($d),
                'codigo' => $this->texto($ws->getCell("E{$r}")->getCalculatedValue()),
                'beneficiario' => $this->texto($ws->getCell("F{$r}")->getCalculatedValue()),
                'descripcion' => $this->texto($ws->getCell("G{$r}")->getCalculatedValue()),
                'clasificador_1' => $this->texto($ws->getCell("H{$r}")->getCalculatedValue()),
                'clasificador_2' => $this->texto($ws->getCell("I{$r}")->getCalculatedValue()),
                'subgrupo_ng' => $this->texto($ws->getCell("J{$r}")->getCalculatedValue()),
                'subgrupo_bl' => $this->texto($ws->getCell("K{$r}")->getCalculatedValue()),
                'semana' => $semana,
                'tipo_documento' => $tipoDocumento,
                'numero_documento' => $this->texto($ws->getCell("P{$r}")->getCalculatedValue()),
                'situacion_cheque' => $this->texto($ws->getCell("Q{$r}")->getCalculatedValue()),
                'importe_usd' => $importeUsd !== null ? round($importeUsd, 2) : null,
                'tipo_cambio_operacion' => $tcOperacion,
                'importe' => round($importe, 2),
                'importe_detalle' => $detalle,
                'tipo_cambio' => $this->numero($ws->getCell("AA{$r}")),
                'color_fondo' => $fill === '#FFFFFF' ? null : $fill,
                'color_texto' => $font === '#000000' ? null : $font,
                'disponible_excel' => $this->numero($ws->getCell("T{$r}")),
            ];
        }
        return $filas;
    }

    /** Hoja Valida + combinaciones usadas en BASE que no están en Valida (el tipo sale del signo de sus importes). */
    private function importarClasificadores(?Worksheet $ws, array $filas): int
    {
        $antes = CajaClasificador::count();
        $orden = 0;
        if ($ws) {
            $tipo = null;
            $grupo = null;
            for ($r = 3; $r <= $ws->getHighestDataRow(); $r++) {
                $f = trim((string) $ws->getCell("F{$r}")->getValue());
                $g = trim((string) $ws->getCell("G{$r}")->getValue());
                $h = $this->texto($ws->getCell("H{$r}")->getValue());
                $i = $this->texto($ws->getCell("I{$r}")->getValue());
                if ($f !== '') {
                    $tipo = str_starts_with(mb_strtoupper($f), 'INGRESO') ? 'INGRESO' : 'EGRESO';
                }
                if ($g !== '') {
                    $grupo = preg_replace('/\s+/', ' ', $g);
                }
                if (!$h || !$i || !$tipo) {
                    continue;
                }
                $this->crearClasificador($tipo, $grupo, $h, $i, ++$orden);
            }
        }

        $usados = [];
        foreach ($filas as $f) {
            if ($f['clasificador_1'] && $f['clasificador_2']) {
                $k = $this->clave($f['clasificador_1'], $f['clasificador_2']);
                $usados[$k] ??= ['c1' => $f['clasificador_1'], 'c2' => $f['clasificador_2'], 'suma' => 0];
                $usados[$k]['suma'] += $f['importe'];
            }
        }
        foreach ($usados as $u) {
            $this->crearClasificador($u['suma'] >= 0 ? 'INGRESO' : 'EGRESO', null, $u['c1'], $u['c2'], ++$orden);
        }

        return CajaClasificador::count() - $antes;
    }

    private function crearClasificador(string $tipo, ?string $grupo, string $c1, string $c2, int $orden): void
    {
        $existe = CajaClasificador::get(['id', 'clasificador_1', 'clasificador_2'])
            ->first(fn($c) => $this->clave($c->clasificador_1, $c->clasificador_2) === $this->clave($c1, $c2));
        if ($existe) {
            return;
        }
        CajaClasificador::create(array_merge([
            'tipo' => $tipo,
            'grupo' => $grupo,
            'clasificador_1' => $c1,
            'clasificador_2' => $c2,
            'orden' => $orden,
        ], CajaMovimientoReglas::colorPorDefecto($c1, $c2)));
    }

    private function importarTiposCambio(?Worksheet $ws): int
    {
        if (!$ws) {
            return 0;
        }
        $filas = [];
        $ahora = now();
        for ($r = 2; $r <= $ws->getHighestDataRow('A'); $r++) {
            $fecha = $ws->getCell("A{$r}")->getCalculatedValue();
            $valor = $ws->getCell("B{$r}")->getCalculatedValue();
            if (is_numeric($fecha) && is_numeric($valor) && $valor > 0) {
                $filas[] = [
                    'fecha' => Carbon::instance(ExcelDate::excelToDateTimeObject($fecha))->toDateString(),
                    'valor' => round((float) $valor, 4),
                    'created_at' => $ahora,
                    'updated_at' => $ahora,
                ];
            }
        }
        foreach (array_chunk($filas, 1000) as $lote) {
            CajaTipoCambio::upsert($lote, ['fecha'], ['valor', 'updated_at']);
        }
        return count($filas);
    }

    /**
     * Notas de la columna AB ("Saldo AQP S/ 536,294.01 Naranja S/ 18,600.10 …") con su suma en AE
     * ("=536294.01+18600.1+24.69+504.32"): cada término es el saldo de una fuente, en el orden de la nota.
     */
    private function importarArqueos(Worksheet $ws, array $filas, array &$avisos): int
    {
        $fechaPorFila = array_column($filas, 'fecha', 'fila');
        $fuentes = [];
        foreach (self::FUENTES as $i => $nombre) {
            $fuentes[mb_strtolower($nombre)] = CajaFuente::firstOrCreate(['nombre' => $nombre], ['orden' => $i + 1]);
        }

        $arqueos = [];
        $ultima = $ws->getHighestDataRow('AB');
        for ($r = 3; $r <= $ultima; $r++) {
            $nota = trim((string) $ws->getCell("AB{$r}")->getValue());
            if (!str_starts_with(mb_strtolower($nota), 'saldo')) {
                continue;
            }
            $fecha = $fechaPorFila[$r] ?? $this->fechaCercana($fechaPorFila, $r);
            if (!$fecha) {
                continue;
            }

            $formula = (string) $ws->getCell("AE{$r}")->getValue();
            $terminos = array_values(array_filter(array_map('trim', preg_split('/\+/', ltrim($formula, '=')) ?: []), 'is_numeric'));
            preg_match_all('/([A-Za-zÁÉÍÓÚáéíóúñÑ\-]+)\s*S\/\s*[\d.,]+/u', $nota, $nombres);
            $nombresNota = array_map(fn($n) => mb_strtolower($n) === 'cuadrillas' ? 'cuadrillas' : mb_strtolower($n), $nombres[1]);

            if (str_contains(mb_strtolower($nota), 'caja bancos')) {
                $nombresNota = ['caja bancos'];
                $terminos = is_numeric($formula) ? [$formula] : $terminos;
            }
            if (!$terminos || count($terminos) !== count($nombresNota)) {
                if ($terminos && array_sum($terminos) != 0) {
                    $avisos[] = "Fila {$r}: no se pudo leer la nota de saldos \"{$nota}\".";
                }
                continue;
            }

            foreach ($nombresNota as $i => $nombre) {
                $fuente = $fuentes[$nombre] ?? null;
                $monto = (float) $terminos[$i];
                if (!$fuente || ($nombre === 'caja bancos' && abs($monto) < 0.005)) {
                    continue;
                }
                $arqueos[$fecha] ??= [];
                $arqueos[$fecha][$fuente->id] = $monto;
            }
        }

        $cierres = app(CajaCierreConsulta::class);
        $creados = 0;
        foreach ($arqueos as $fecha => $montos) {
            // Meses cerrados no se tocan; un día que ya tiene arqueo se conserva (al reemplazar, los del mes ya se borraron)
            if ($cierres->fechaCerrada($fecha) || CajaArqueo::whereDate('fecha', $fecha)->exists()) {
                continue;
            }
            $creados++;
            $arqueo = CajaArqueo::create(['fecha' => $fecha, 'observacion' => 'Importado del Excel', 'creado_por' => auth()->id()]);
            foreach ($montos as $fuenteId => $monto) {
                CajaArqueoDetalle::create(['caja_arqueo_id' => $arqueo->id, 'caja_fuente_id' => $fuenteId, 'monto' => round($monto, 2)]);
            }
        }
        return $creados;
    }

    private function fechaCercana(array $fechaPorFila, int $fila): ?string
    {
        $anteriores = array_filter($fechaPorFila, fn($f, $r) => $r <= $fila, ARRAY_FILTER_USE_BOTH);
        return $anteriores ? end($anteriores) : null;
    }

    private function numero(Cell $celda): ?float
    {
        // El valor que Excel ya calculó al guardar: exacto y sin recalcular miles de BUSCARV
        $v = $celda->isFormula() ? $celda->getOldCalculatedValue() : $celda->getValue();
        if ($v === null && $celda->isFormula()) {
            try {
                $v = $celda->getCalculatedValue();
            } catch (\Throwable) {
                $v = null;
            }
        }
        if ($v === null || $v === '' || !is_numeric($v)) {
            return null;
        }
        return (float) $v;
    }

    private function texto($v): ?string
    {
        if ($v === null) {
            return null;
        }
        $v = trim((string) $v);
        return $v === '' ? null : $v;
    }

    /** Mismo color o casi (el Excel tiene #B5C6E8 donde la regla dice #B4C6E7). */
    private function mismoColor(?string $a, ?string $b): bool
    {
        if ($a === null || $b === null) {
            return $a === $b;
        }
        foreach ([1, 3, 5] as $i) {
            if (abs(hexdec(substr($a, $i, 2)) - hexdec(substr($b, $i, 2))) > 6) {
                return false;
            }
        }
        return true;
    }

    /**
     * Clave para reconocer un clasificador aunque esté escrito distinto: sin mayúsculas, tildes, espacios de más
     * ni la palabra "DE" ("Venta Naranja" = "VENTA DE NARANJA").
     */
    private function clave(?string $c1, ?string $c2): string
    {
        $n = function ($t) {
            $t = mb_strtoupper(\Illuminate\Support\Str::ascii(trim((string) $t)));
            $t = preg_replace('/\bDE\b/', ' ', $t);
            return trim(preg_replace('/\s+/', ' ', $t));
        };
        return $n($c1) . '|' . $n($c2);
    }
}

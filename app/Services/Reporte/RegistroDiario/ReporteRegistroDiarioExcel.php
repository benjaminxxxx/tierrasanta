<?php

namespace App\Services\Reporte\RegistroDiario;

use App\Models\ConsolidadoRiego;
use App\Models\CuadRegistroDiario;
use App\Models\Labores;
use App\Models\LaboresRiego;
use App\Models\PlanMensualDetalle;
use App\Models\ReporteDiarioRiego;
use Illuminate\Support\Carbon;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * Reporte diario en Excel (plantilla public/templates/rpt_registro_diario.xlsx): los registros diarios de un
 * día tal como están en planilla, cuadrilla y riego.
 *
 * - LABORES: solo las labores usadas ese día (planilla + cuadrilla) y las labores de riego usadas ese día.
 * - PLANILLA Y CUADRILLA: cada empleado de planilla con su asistencia y sus actividades; debajo, la cuadrilla
 *   agrupada (cuántos cuadrilleros hicieron las mismas actividades).
 * - CUADRILLA: el mismo detalle de cuadrilla, cuadrillero por cuadrillero, con el total de horas al final.
 * - REGADORES: el diseño de un regador (25 filas) repetido uno debajo de otro, uno por regador del día.
 *
 * Los totales de horas no se recalculan en el Excel: son los que ya tiene el sistema.
 */
class ReporteRegistroDiarioExcel
{
    private const PLANTILLA = 'templates/rpt_registro_diario.xlsx';

    private const FILA_INICIO = 7;
    private const BLOQUES = 7; // (campo, labor, hora inicio, hora de salida) por fila

    // PLANILLA Y CUADRILLA: A n°, B nombre, C asistencia, D n° de cuadrilleros, actividades desde E
    private const PYC = ['primer_bloque' => 5, 'total' => 'AG'];
    private const FILAS_PLANILLA = 100;
    private const FILAS_CUADRILLA_GRUPO = 20;

    // CUADRILLA: A n°, B nombre, actividades desde C
    private const CUAD = ['primer_bloque' => 3, 'total' => 'AE'];

    // REGADORES: un regador ocupa 25 filas (la primera y la última quedan libres) y las columnas A–Y
    private const RIEGO_ALTO = 25;
    private const RIEGO_ULTIMA_COL = 25; // Y
    /** Grupos de la grilla: columna del campo => [primera fila, última fila]. El de K tiene una fila más. */
    private const RIEGO_GRUPOS = ['A' => [11, 22], 'F' => [11, 22], 'K' => [11, 23], 'P' => [11, 22], 'U' => [11, 22]];
    private const RIEGO_FILA_TOTALES = 23;
    private const RIEGO_CELDA_OBSERVACIONES = 'C24';
    /** Etiquetas de la plantilla que no coinciden con el nombre del campo en el sistema. */
    private const RIEGO_ALIAS = ['nb' => 'naranjosb'];

    private const FORMATO_HORA = 'h:mm';
    private const FORMATO_DURACION = '[h]:mm';

    /** @return array{ruta: string, nombre: string} archivo temporal generado */
    public function generar(string $fecha): array
    {
        $fecha = Carbon::parse($fecha)->startOfDay();
        $libro = IOFactory::load(public_path(self::PLANTILLA));

        $planilla = $this->planilla($fecha);
        $cuadrilla = $this->cuadrilla($fecha);
        $regadores = $this->regadores($fecha);

        $this->hojaLabores($libro->getSheetByName('LABORES'), $planilla, $cuadrilla, $regadores);
        $this->hojaPlanillaYCuadrilla($libro->getSheetByName('PLANILLA Y CUADRILLA'), $fecha, $planilla, $cuadrilla);
        $this->hojaCuadrilla($libro->getSheetByName('CUADRILLA'), $fecha, $cuadrilla);
        $this->hojaRegadores($libro->getSheetByName('REGADORES'), $fecha, $regadores);
        $libro->setActiveSheetIndexByName('PLANILLA Y CUADRILLA');

        $ruta = storage_path('app/temp/' . uniqid('registro_diario_') . '.xlsx');
        if (!is_dir(dirname($ruta))) {
            mkdir(dirname($ruta), 0775, true);
        }
        (new Xlsx($libro))->save($ruta);

        return ['ruta' => $ruta, 'nombre' => 'Reporte diario ' . $fecha->format('Y-m-d') . '.xlsx'];
    }

    // ------------------------------------------------------------------ datos

    /**
     * Empleados de la planilla del mes, en su orden, con lo registrado ese día.
     *
     * @return array<int, array{nombre: string, asistencia: ?string, horas: ?float, actividades: array}>
     */
    private function planilla(Carbon $fecha): array
    {
        return PlanMensualDetalle::whereHas('planillaMensual', fn($q) => $q->where('mes', $fecha->month)->where('anio', $fecha->year))
            ->with(['registrosDiarios' => fn($q) => $q->whereDate('fecha', $fecha)->with('detalles')])
            ->orderBy('orden')->orderBy('nombres')
            ->get()
            ->map(function (PlanMensualDetalle $detalle) {
                $registro = $detalle->registrosDiarios->first();
                return [
                    'nombre' => $detalle->nombres,
                    'asistencia' => $registro?->asistencia,
                    'horas' => $registro ? (float) $registro->total_horas : null,
                    'actividades' => $registro ? $this->actividades($registro->detalles->sortBy([['orden', 'asc'], ['hora_inicio', 'asc']])) : [],
                ];
            })->all();
    }

    /**
     * Cuadrilleros con registro ese día.
     *
     * @return array<int, array{nombre: string, grupo: ?string, horas: float, actividades: array}>
     */
    private function cuadrilla(Carbon $fecha): array
    {
        return CuadRegistroDiario::whereDate('fecha', $fecha)
            ->with(['cuadrillero' => fn($q) => $q->withTrashed(), 'detalleHoras'])
            ->get()
            ->map(fn(CuadRegistroDiario $registro) => [
                'nombre' => $registro->cuadrillero?->nombres ?? 'Cuadrillero',
                'grupo' => $registro->codigo_grupo,
                'horas' => (float) $registro->total_horas,
                'actividades' => $this->actividades($registro->detalleHoras->sortBy('hora_inicio')),
            ])
            ->sortBy([['grupo', 'asc'], ['nombre', 'asc']])->values()->all();
    }

    /** @return array<int, array{campo: ?string, labor: mixed, inicio: ?string, fin: ?string}> */
    private function actividades($detalles): array
    {
        return $detalles->map(fn($d) => [
            'campo' => $d->campo_nombre,
            'labor' => $d->codigo_labor,
            'inicio' => $d->hora_inicio,
            'fin' => $d->hora_fin,
        ])->values()->all();
    }

    /**
     * Regadores del día con sus riegos (van a la grilla), sus otras labores (van a observaciones) y los
     * totales que ya calculó el sistema (pesos, cruces, horas acumuladas…): no se recalculan aquí.
     *
     * @return array<int, array<string, mixed>>
     */
    private function regadores(Carbon $fecha): array
    {
        $laboresDeRiego = LaboresRiego::where('es_riego', true)->pluck('nombre_labor')->map(fn($n) => mb_strtolower(trim($n)))->all() ?: ['riego'];

        return ConsolidadoRiego::whereDate('fecha', $fecha)->orderBy('id')->get()
            ->map(function (ConsolidadoRiego $consolidado) use ($laboresDeRiego) {
                $detalles = ReporteDiarioRiego::where('consolidado_id', $consolidado->id)->orderBy('hora_inicio')->orderBy('id')->get();
                [$riegos, $otras] = $detalles->partition(fn($d) => in_array(mb_strtolower(trim((string) $d->tipo_labor)), $laboresDeRiego, true));
                $fila = fn($d) => [
                    'campo' => trim((string) $d->campo), 'inicio' => $d->hora_inicio, 'fin' => $d->hora_fin, 'labor' => $d->tipo_labor,
                    'descripcion' => $d->descripcion, 'horas_sistema' => (float) $d->horas_ponderadas,
                ];

                return [
                    'nombre' => trim((string) $consolidado->trabajador_nombre) ?: 'Regador',
                    'hora_inicio' => $consolidado->hora_inicio,
                    'hora_fin' => $consolidado->hora_fin,
                    'minutos_regados' => (int) $consolidado->minutos_regados,
                    'minutos_jornal' => (int) $consolidado->minutos_jornal,
                    'riegos' => $riegos->map($fila)->values()->all(),
                    'otras' => $otras->map($fila)->values()->all(),
                ];
            })->all();
    }

    // ------------------------------------------------------------------ labores

    private function hojaLabores(Worksheet $hoja, array $planilla, array $cuadrilla, array $regadores): void
    {
        $codigos = collect([...$planilla, ...$cuadrilla])->flatMap(fn($p) => array_column($p['actividades'], 'labor'))->filter()->unique();
        $labores = Labores::withTrashed()->whereIn('codigo', $codigos)->orderBy('codigo')->get(['codigo', 'nombre_labor'])
            ->map(fn($l) => [$l->codigo, $l->nombre_labor])->all();

        $riego = collect($regadores)->flatMap(fn($r) => array_column([...$r['riegos'], ...$r['otras']], 'labor'))
            ->filter()->unique()->sort(SORT_NATURAL | SORT_FLAG_CASE)->values()
            ->map(fn($nombre, $i) => [$i + 1, $nombre])->all();

        $this->llenarTabla($hoja, 'tbl_labores', 'A', 'B', $labores);
        $this->llenarTabla($hoja, 'tbl_labores_riego', 'D', 'E', $riego);
    }

    /** Escribe las filas de una tabla (dos columnas, encabezado en la fila 2) y ajusta su rango. */
    private function llenarTabla(Worksheet $hoja, string $nombre, string $col1, string $col2, array $filas): void
    {
        $tabla = $hoja->getTableByName($nombre);
        $ultimaActual = (int) preg_replace('/\D/', '', explode(':', $tabla->getRange())[1]);
        for ($r = 3; $r <= max($ultimaActual, count($filas) + 2); $r++) {
            $fila = $filas[$r - 3] ?? [null, null];
            $hoja->setCellValue("{$col1}{$r}", $fila[0]);
            $hoja->setCellValue("{$col2}{$r}", $fila[1]);
        }
        // Una tabla necesita al menos una fila de datos
        $tabla->setRange("{$col1}2:{$col2}" . (max(1, count($filas)) + 2));
    }

    // ------------------------------------------------------------------ planilla y cuadrilla

    private function hojaPlanillaYCuadrilla(Worksheet $hoja, Carbon $fecha, array $planilla, array $cuadrilla): void
    {
        // La cuadrilla va agrupada: cuántos hicieron exactamente las mismas actividades
        $grupos = [];
        foreach ($cuadrilla as $c) {
            if (!$c['actividades']) {
                continue;
            }
            $clave = json_encode($c['actividades']);
            $grupos[$clave] ??= ['cantidad' => 0, 'horas' => $c['horas'], 'actividades' => $c['actividades']];
            $grupos[$clave]['cantidad']++;
        }
        $grupos = array_values($grupos);

        $this->encabezado($hoja, $fecha);
        $filasPlanilla = $this->asegurarFilas($hoja, self::FILA_INICIO, self::FILAS_PLANILLA, count($planilla));
        // La zona de cuadrilla empieza donde termina la de planilla (que puede haber crecido)
        $inicioCuadrilla = self::FILA_INICIO + $filasPlanilla;
        $filasCuadrilla = $this->asegurarFilas($hoja, $inicioCuadrilla, self::FILAS_CUADRILLA_GRUPO, count($grupos));

        for ($i = 0; $i < $filasPlanilla; $i++) {
            $r = self::FILA_INICIO + $i;
            $p = $planilla[$i] ?? null;
            $this->limpiarFila($hoja, $r, self::PYC);
            $hoja->setCellValue("A{$r}", $i + 1);
            if ($p) {
                $hoja->setCellValue("B{$r}", $p['nombre']);
                $hoja->setCellValue("C{$r}", $p['asistencia']);
                $this->escribirActividades($hoja, $r, self::PYC, $p['actividades'], $p['horas']);
            }
            $hoja->getRowDimension($r)->setVisible($p !== null);
        }
        for ($i = 0; $i < $filasCuadrilla; $i++) {
            $r = $inicioCuadrilla + $i;
            $g = $grupos[$i] ?? null;
            $this->limpiarFila($hoja, $r, self::PYC);
            $hoja->setCellValue("B{$r}", 'Cuadrilla');
            if ($g) {
                $hoja->setCellValue("D{$r}", $g['cantidad']);
                $this->escribirActividades($hoja, $r, self::PYC, $g['actividades'], $g['horas']);
            }
            $hoja->getRowDimension($r)->setVisible($g !== null);
        }
    }

    /** Cuadrillero por cuadrillero; los bordes llegan hasta el último y debajo va el total de horas. */
    private function hojaCuadrilla(Worksheet $hoja, Carbon $fecha, array $cuadrilla): void
    {
        $this->encabezado($hoja, $fecha);
        $total = self::CUAD['total'];
        $primera = self::FILA_INICIO;
        $ultimaCol = Coordinate::columnIndexFromString($total);
        $alto = $hoja->getRowDimension($primera)->getRowHeight();

        // Filas ya numeradas (con bordes) de la plantilla
        for ($r = $primera; $hoja->getCell("A{$r}")->getValue() !== null; $r++) {
            $ultimaPlantilla = $r;
        }
        // Las que falten copian el formato de la primera, celda por celda y antes de escribir nada en ella
        for ($r = ($ultimaPlantilla ?? $primera) + 1; $r < $primera + count($cuadrilla); $r++) {
            for ($c = 1; $c <= $ultimaCol; $c++) {
                $col = Coordinate::stringFromColumnIndex($c);
                $formato = $hoja->getCell("{$col}{$primera}")->getXfIndex();
                $hoja->getCell("{$col}{$r}")->setXfIndex($formato);
            }
            $hoja->getRowDimension($r)->setRowHeight($alto);
        }

        foreach ($cuadrilla as $i => $c) {
            $r = $primera + $i;
            $this->limpiarFila($hoja, $r, self::CUAD);
            $hoja->setCellValue("A{$r}", $i + 1);
            $hoja->setCellValue("B{$r}", $c['nombre']);
            $this->escribirActividades($hoja, $r, self::CUAD, $c['actividades'], $c['horas']);
        }

        $filaTotal = $primera + count($cuadrilla);
        for ($r = $filaTotal; $r <= ($ultimaPlantilla ?? $primera); $r++) {
            $hoja->setCellValue("A{$r}", null);
            $hoja->getStyle("A{$r}:{$total}{$r}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_NONE);
        }
        if ($cuadrilla) {
            $etiqueta = Coordinate::stringFromColumnIndex(Coordinate::columnIndexFromString($total) - 1) . $filaTotal;
            $hoja->setCellValue($etiqueta, 'TOTAL');
            $hoja->setCellValue("{$total}{$filaTotal}", "=SUM({$total}{$primera}:{$total}" . ($filaTotal - 1) . ')');
            $celdas = $hoja->getStyle("{$etiqueta}:{$total}{$filaTotal}");
            $celdas->getFont()->setBold(true);
            $celdas->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
            $hoja->getStyle("{$total}{$filaTotal}")->getNumberFormat()->setFormatCode('0.00');
            // El borde inferior de la última fila de datos se pierde al quitar los de la fila siguiente
            $hoja->getStyle('A' . ($filaTotal - 1) . ":{$total}" . ($filaTotal - 1))->getBorders()->getBottom()->setBorderStyle(Border::BORDER_THIN);
        }
    }

    private function encabezado(Worksheet $hoja, Carbon $fecha): void
    {
        $hoja->setCellValue('A3', ExcelDate::PHPToExcel($fecha));
        $hoja->setCellValue('A4', auth()->user()?->name ?? '');
    }

    /**
     * La plantilla trae $porDefecto filas desde $inicio; si hacen falta más, se insertan dentro del bloque (así
     * las fórmulas del pie que suman o cuentan ese rango lo siguen cubriendo). Devuelve cuántas filas hay.
     */
    private function asegurarFilas(Worksheet $hoja, int $inicio, int $porDefecto, int $necesarias): int
    {
        if ($necesarias > $porDefecto) {
            $hoja->insertNewRowBefore($inicio + $porDefecto - 1, $necesarias - $porDefecto);
        }
        return max($porDefecto, $necesarias);
    }

    /** Deja la fila vacía de B hasta el total de horas. */
    private function limpiarFila(Worksheet $hoja, int $r, array $diseno): void
    {
        $ultima = Coordinate::columnIndexFromString($diseno['total']);
        for ($c = 2; $c <= $ultima; $c++) {
            $hoja->setCellValue(Coordinate::stringFromColumnIndex($c) . $r, null);
        }
    }

    /** @param array{primer_bloque:int, total:string} $diseno */
    private function escribirActividades(Worksheet $hoja, int $r, array $diseno, array $actividades, ?float $horas): void
    {
        foreach (array_slice($actividades, 0, self::BLOQUES) as $i => $a) {
            $col = fn(int $n) => Coordinate::stringFromColumnIndex($diseno['primer_bloque'] + $i * 4 + $n) . $r;
            $hoja->setCellValue($col(0), $a['campo']);
            $hoja->setCellValue($col(1), $a['labor']);
            $this->escribirHora($hoja, $col(2), $a['inicio']);
            $this->escribirHora($hoja, $col(3), $a['fin']);
        }
        // El total de horas del registro en el sistema (ya no se descuenta la hora de almuerzo con una fórmula)
        $hoja->setCellValue($diseno['total'] . $r, $horas);
    }

    // ------------------------------------------------------------------ regadores

    /** El diseño de un regador (filas 1–25 de la plantilla) se repite uno debajo de otro. */
    private function hojaRegadores(Worksheet $hoja, Carbon $fecha, array $regadores): void
    {
        // Campos impresos en la grilla de la plantilla, en su orden: [columna, fila, etiqueta]
        $casillas = [];
        foreach (self::RIEGO_GRUPOS as $col => [$desde, $hasta]) {
            for ($r = $desde; $r <= $hasta; $r++) {
                $casillas[] = [$col, $r, trim((string) $hoja->getCell("{$col}{$r}")->getValue())];
            }
        }

        // Primero las copias (el bloque original aún está sin llenar) y luego se llena cada uno
        for ($k = 1; $k < count($regadores); $k++) {
            $this->copiarBloqueRegador($hoja, $k * self::RIEGO_ALTO);
        }
        foreach ($regadores ?: [null] as $k => $regador) {
            $this->llenarRegador($hoja, $k * self::RIEGO_ALTO, $fecha, $regador, $casillas);
            // Cada regador en su página al imprimir
            if ($k > 0) {
                $hoja->setBreak('A' . ($k * self::RIEGO_ALTO), Worksheet::BREAK_ROW);
            }
        }
    }

    /** Copia textos fijos, estilos, alturas y celdas combinadas del bloque original $desplazamiento filas más abajo. */
    private function copiarBloqueRegador(Worksheet $hoja, int $desplazamiento): void
    {
        for ($r = 1; $r <= self::RIEGO_ALTO; $r++) {
            $destino = $r + $desplazamiento;
            $hoja->getRowDimension($destino)->setRowHeight($hoja->getRowDimension($r)->getRowHeight());
            for ($c = 1; $c <= self::RIEGO_ULTIMA_COL; $c++) {
                $col = Coordinate::stringFromColumnIndex($c);
                $origen = $hoja->getCell("{$col}{$r}");
                $celda = $hoja->getCell("{$col}{$destino}");
                $celda->setXfIndex($origen->getXfIndex());
                // Las fórmulas no se copian: cada bloque escribe las suyas con sus propias filas
                $valor = $origen->getValue();
                $celda->setValue(is_string($valor) && str_starts_with($valor, '=') ? null : $valor);
            }
        }
        foreach ($hoja->getMergeCells() as $rango) {
            [$inicio, $fin] = Coordinate::rangeBoundaries($rango);
            if ($fin[1] <= self::RIEGO_ALTO) {
                $hoja->mergeCells(Coordinate::stringFromColumnIndex($inicio[0]) . ($inicio[1] + $desplazamiento) . ':'
                    . Coordinate::stringFromColumnIndex($fin[0]) . ($fin[1] + $desplazamiento));
            }
        }
    }

    /** @param array<int, array{0:string, 1:int, 2:string}> $casillas */
    private function llenarRegador(Worksheet $hoja, int $d, Carbon $fecha, ?array $regador, array $casillas): void
    {
        $celda = fn(string $ref) => preg_replace_callback('/\d+/', fn($m) => (int) $m[0] + $d, $ref);

        $hoja->setCellValue($celda('H5'), ExcelDate::PHPToExcel($fecha));
        $hoja->setCellValue($celda('B7'), $regador['nombre'] ?? '');
        $this->escribirHora($hoja, $celda('H6'), $regador['hora_inicio'] ?? null);
        $this->escribirHora($hoja, $celda('H7'), $regador['hora_fin'] ?? null);
        // Totales tal como los calculó el sistema, no una fórmula
        $this->escribirDuracion($hoja, $celda('O5'), $regador ? $regador['minutos_regados'] / 60 : null);
        $this->escribirDuracion($hoja, $celda('O6'), $regador ? $regador['minutos_jornal'] / 60 : null);

        [$asignados, $sobrantes] = $this->ubicarRiegos($regador['riegos'] ?? [], $casillas);

        $totales = [];
        foreach ($casillas as $i => [$colCampo, $fila, $etiqueta]) {
            $n = Coordinate::columnIndexFromString($colCampo);
            [$colInicio, $colFin, $colTotal, $colSistema] = array_map(fn($k) => Coordinate::stringFromColumnIndex($n + $k), [1, 2, 3, 4]);
            $r = $fila + $d;
            $riego = $asignados[$i] ?? null;
            $horas = $riego ? $this->horasEntre($riego['inicio'], $riego['fin']) : null;

            $hoja->setCellValue("{$colCampo}{$r}", $riego['campo'] ?? $etiqueta);
            $this->escribirHora($hoja, "{$colInicio}{$r}", $riego['inicio'] ?? null);
            $this->escribirHora($hoja, "{$colFin}{$r}", $riego['fin'] ?? null);
            $this->escribirDuracion($hoja, "{$colTotal}{$r}", $horas);
            $this->escribirDuracion($hoja, "{$colSistema}{$r}", $riego && $riego['horas_sistema'] > 0 ? $riego['horas_sistema'] : null);
            $totales[$colCampo][$colTotal] = ($totales[$colCampo][$colTotal] ?? 0) + ($horas ?? 0);
            $totales[$colCampo][$colSistema] = ($totales[$colCampo][$colSistema] ?? 0) + ($riego['horas_sistema'] ?? 0);
        }
        // Totales por grupo de columnas (el grupo K no tiene: usa esa fila para un campo más)
        foreach ($totales as $colCampo => $columnas) {
            if (self::RIEGO_GRUPOS[$colCampo][1] >= self::RIEGO_FILA_TOTALES) {
                continue;
            }
            foreach ($columnas as $col => $horas) {
                $this->escribirDuracion($hoja, $col . (self::RIEGO_FILA_TOTALES + $d), $horas > 0 ? $horas : null);
            }
        }

        $hora = fn($h) => $h ? substr($h, 0, 5) : '?';
        $notas = array_map(
            fn($o) => "{$hora($o['inicio'])}-{$hora($o['fin'])} " . trim(($o['campo'] !== '' ? "{$o['campo']}: " : '') . $o['labor'] . ($o['descripcion'] ? " ({$o['descripcion']})" : '')),
            $regador['otras'] ?? []
        );
        foreach ($sobrantes as $s) {
            $notas[] = "{$hora($s['inicio'])}-{$hora($s['fin'])} {$s['campo']}: riego (no entró en la grilla)";
        }
        $hoja->setCellValue($celda(self::RIEGO_CELDA_OBSERVACIONES), implode('; ', $notas));
    }

    /**
     * Cada riego va a la casilla de su campo. Los campos que la grilla no tiene impresos (o un segundo riego
     * del mismo campo) ocupan las últimas casillas cuyo campo no se regó ese día.
     *
     * @return array{0: array<int, array>, 1: array<int, array>} [casilla => riego, riegos que no entraron]
     */
    private function ubicarRiegos(array $riegos, array $casillas): array
    {
        $clave = fn(string $t) => self::RIEGO_ALIAS[mb_strtolower(trim($t))] ?? mb_strtolower(trim($t));
        $porEtiqueta = [];
        foreach ($casillas as $i => [, , $etiqueta]) {
            $porEtiqueta[$clave($etiqueta)] ??= $i; // FDM está impreso varias veces: vale la primera
        }

        $asignados = [];
        $sinCasilla = [];
        foreach ($riegos as $riego) {
            $i = $porEtiqueta[$clave($riego['campo'])] ?? null;
            if ($i !== null && !isset($asignados[$i])) {
                $asignados[$i] = $riego;
            } else {
                $sinCasilla[] = $riego;
            }
        }

        $regados = array_map(fn($r) => $clave($r['campo']), $riegos);
        $libres = array_values(array_filter(array_reverse(array_keys($casillas)),
            fn($i) => !isset($asignados[$i]) && !in_array($clave($casillas[$i][2]), $regados, true)));
        // Las casillas libres se toman desde el final, pero los riegos quedan en orden de hora hacia abajo
        $usar = array_reverse(array_slice($libres, 0, count($sinCasilla)));
        foreach ($usar as $n => $i) {
            $asignados[$i] = $sinCasilla[$n];
        }

        return [$asignados, array_slice($sinCasilla, count($usar))];
    }

    // ------------------------------------------------------------------ celdas

    private function horasEntre(?string $inicio, ?string $fin): ?float
    {
        if (!$inicio || !$fin) {
            return null;
        }
        $minutos = Carbon::parse($inicio)->diffInMinutes(Carbon::parse($fin), false);
        return ($minutos < 0 ? $minutos + 1440 : $minutos) / 60;
    }

    /** Hora 'HH:MM[:SS]' como hora de Excel (fracción de día). */
    private function escribirHora(Worksheet $hoja, string $celda, ?string $hora): void
    {
        if (!$hora || !preg_match('/^(\d{1,2}):(\d{2})/', $hora, $m)) {
            $hoja->setCellValue($celda, null);
            return;
        }
        $hoja->setCellValue($celda, ((int) $m[1] * 60 + (int) $m[2]) / 1440);
        $hoja->getStyle($celda)->getNumberFormat()->setFormatCode(self::FORMATO_HORA);
    }

    /** Cantidad de horas como duración de Excel (13.5 → 13:30). */
    private function escribirDuracion(Worksheet $hoja, string $celda, ?float $horas): void
    {
        $hoja->setCellValue($celda, $horas === null ? null : $horas / 24);
        if ($horas !== null) {
            $hoja->getStyle($celda)->getNumberFormat()->setFormatCode(self::FORMATO_DURACION);
        }
    }
}

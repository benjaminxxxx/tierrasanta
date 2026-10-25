<?php

namespace App\Services\Reporte\RegistroDiario;

use App\Models\AcumulacionUso;
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
use App\Services\Sistema\Empresa\SistemaEmpresaProceso;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
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
 * - REGADORES: se arma desde cero como la pantalla de riego: empresa, y por regador sus datos del día y todas sus filas.
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

    // REGADORES: columna => [título, ancho] (las de la pantalla de riego)
    private const RIEGO_COLUMNAS = [
        'A' => ['N°', 5], 'B' => ['Campo', 12], 'C' => ['Hora inicio', 10], 'D' => ['Hora fin', 10], 'E' => ['Total horas', 11],
        'F' => ['Tipo de labor', 30], 'G' => ['Descripción', 30], 'H' => ['Sin horas', 9], 'I' => ['De otro día', 10], 'J' => ['Jornal', 9],
    ];
    // Colores de DESIGN.md
    private const NOCHE = '10212D';
    private const NIEBLA = 'F3F3F7';
    private const GRIS = '6F737B';

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

        $this->hojaLabores($libro->getSheetByName('LABORES'), $fecha, $planilla, $cuadrilla, $regadores);
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
        $orden = $this->ordenCuadrilla($fecha);
        return CuadRegistroDiario::whereDate('fecha', $fecha)
            ->with(['cuadrillero' => fn($q) => $q->withTrashed(), 'detalleHoras'])
            ->get()
            ->map(fn(CuadRegistroDiario $registro) => [
                'nombre' => $registro->cuadrillero?->nombres ?? 'Cuadrillero',
                'grupo' => $registro->codigo_grupo,
                'horas' => (float) $registro->total_horas,
                'actividades' => $this->actividades($registro->detalleHoras->sortBy('hora_inicio')),
                // Mismo orden que el registro diario de cuadrilla (lista del tramo semanal); los que no están en
                // ninguna lista van al final por grupo y nombre
                'orden' => $orden[$registro->codigo_grupo . '|' . $registro->cuadrillero_id] ?? PHP_INT_MAX,
            ])
            ->sortBy([['orden', 'asc'], ['grupo', 'asc'], ['nombre', 'asc']])->values()->all();
    }

    /**
     * Orden de llegada de la cuadrilla: la lista oficial de cada tramo semanal que cubre la fecha (grupos y
     * cuadrilleros en su orden), igual que /cuadrilla/registro-diario.
     *
     * @return array<string, int> "codigo_grupo|cuadrillero_id" => posición
     */
    private function ordenCuadrilla(Carbon $fecha): array
    {
        $orden = [];
        foreach (\App\Services\Cuadrilla\TramoLaboralServicio::tramosEnFecha($fecha->toDateString()) as $tramo) {
            foreach (\App\Services\Cuadrilla\TramoLaboralServicio::obtenerListaOficial($tramo->id) as $grupo) {
                foreach ($grupo->cuadrilleros as $c) {
                    $orden[$grupo->codigo_grupo . '|' . $c->cuadrillero_id] ??= count($orden);
                }
            }
        }
        return $orden;
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
        $inicioSemana = $fecha->copy()->startOfWeek(Carbon::MONDAY)->toDateString();
        $finSemana = $fecha->copy()->endOfWeek(Carbon::SUNDAY)->toDateString();

        return ConsolidadoRiego::with('registrosDiarios')->whereDate('fecha', $fecha)->orderBy('id')->get()
            ->map(function (ConsolidadoRiego $consolidado) use ($laboresDeRiego, $fecha, $inicioSemana, $finSemana) {
                $esRiego = fn($d) => in_array(mb_strtolower(trim((string) $d->tipo_labor)), $laboresDeRiego, true);
                // Mismo orden que la pantalla: riego primero, luego por labor y hora
                $detalles = $consolidado->registrosDiarios->where('por_acumulacion', false)
                    ->sortBy(fn($d) => ($esRiego($d) ? '0' : '1') . mb_strtolower((string) $d->tipo_labor) . '|' . $d->hora_inicio)->values();
                [$riegos, $otras] = $detalles->partition($esRiego);
                $fila = fn($d) => [
                    'campo' => trim((string) $d->campo), 'inicio' => $d->hora_inicio, 'fin' => $d->hora_fin, 'labor' => $d->tipo_labor,
                    'descripcion' => $d->descripcion, 'horas_sistema' => (float) $d->horas_ponderadas,
                    'sin_horas' => (bool) $d->sh, 'otro_dia' => (bool) $d->referido_otro_dia, 'es_riego' => $esRiego($d),
                ];
                $acumulado = $consolidado->registrosDiarios->firstWhere('por_acumulacion', true);
                $mismo = fn() => ConsolidadoRiego::where('trabajador_type', $consolidado->trabajador_type)->where('trabajador_id', $consolidado->trabajador_id);

                return [
                    'nombre' => trim((string) $consolidado->trabajador_nombre) ?: 'Regador',
                    'tipo' => $consolidado->alias_origen,
                    'es_planilla' => $consolidado->trabajador_type === \App\Models\PlanEmpleado::class,
                    'sincronizado' => (bool) $consolidado->sincronizado,
                    'no_acumular' => (bool) $consolidado->no_acumular_horas,
                    'hora_inicio' => $consolidado->hora_inicio,
                    'hora_fin' => $consolidado->hora_fin,
                    'almuerzo_inicio' => $consolidado->hora_inicio_almuerzo,
                    'almuerzo_fin' => $consolidado->hora_fin_almuerzo,
                    'minutos_regados' => (int) $consolidado->minutos_regados,
                    'minutos_jornal' => (int) $consolidado->minutos_jornal,
                    'minutos_acumulados' => (int) $consolidado->minutos_acumulados,
                    'jornal_computable' => (float) $consolidado->jornal_computable,
                    'minutos_semana' => (int) $mismo()->whereBetween('fecha', [$inicioSemana, $finSemana])->sum('minutos_jornal'),
                    'minutos_mes' => (int) $mismo()->whereYear('fecha', $fecha->year)->whereMonth('fecha', $fecha->month)->sum('minutos_jornal'),
                    'acumulado_usado' => $acumulado ? [
                        'horas' => $this->horasEntre($acumulado->hora_inicio, $acumulado->hora_fin),
                        'origenes' => AcumulacionUso::with('consolidadoOrigen')->where('consolidado_destino_id', $consolidado->id)->get()
                            ->map(fn($u) => Carbon::parse($u->consolidadoOrigen?->fecha)->format('d/m') . ' (' . intdiv($u->minutos_consumidos, 60) . 'h '
                                . ($u->minutos_consumidos % 60) . 'min)')->all(),
                    ] : null,
                    'detalles' => $detalles->map($fila)->all(),
                    'riegos' => $riegos->map($fila)->values()->all(),
                    'otras' => $otras->map($fila)->values()->all(),
                ];
            })->all();
    }

    // ------------------------------------------------------------------ labores

    private function hojaLabores(Worksheet $hoja, Carbon $fecha, array $planilla, array $cuadrilla, array $regadores): void
    {
        // La labor que era cada código ese día (un código reutilizado después conserva su nombre de entonces)
        $vigencias = app(\App\Services\Campo\Labor\CampoLaborVigenciaConsulta::class);
        $labores = collect([...$planilla, ...$cuadrilla])->flatMap(fn($p) => array_column($p['actividades'], 'labor'))->filter()->unique()->sort()
            ->map(fn($codigo) => [$codigo, $vigencias->nombre($codigo, $fecha)])->values()->all();

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
        // Empresa (Sistema → Configuración) en la fila 1, del mismo ancho que el título
        $empresa = SistemaEmpresaProceso::datos();
        if ($empresa['razon_social']) {
            $ancho = collect($hoja->getMergeCells())->first(fn($m) => str_starts_with($m, 'A2:'));
            $ultima = $ancho ? preg_replace('/\d+/', '', explode(':', $ancho)[1]) : 'J';
            $hoja->mergeCells("A1:{$ultima}1");
            $hoja->setCellValue('A1', $empresa['razon_social'] . ($empresa['ruc'] ? ' · RUC ' . $empresa['ruc'] : ''));
            $hoja->getStyle('A1')->getFont()->setBold(true)->setSize(11);
            $hoja->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        }
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

    /**
     * Hoja REGADORES: se arma desde cero, igual que la pantalla de riego. Encabezado con la empresa (Sistema →
     * Configuración) y, por regador, sus datos del día (inicio, fin, almuerzo, horas de riego y jornal, acumulado,
     * semana y mes) y todas sus filas tal como están en el sistema (campo, horas, labor, descripción, sin horas, de otro
     * día, jornal). Sin casillas vacías de campos que no se regaron. Cada regador en su página al imprimir.
     */
    private function hojaRegadores(Worksheet $plantilla, Carbon $fecha, array $regadores): void
    {
        $libro = $plantilla->getParent();
        $indice = $libro->getIndex($plantilla);
        $libro->removeSheetByIndex($indice);
        $hoja = new Worksheet($libro, 'REGADORES');
        $libro->addSheet($hoja, $indice);

        $hoja->getDefaultRowDimension()->setRowHeight(18);
        $libro->getDefaultStyle()->getFont()->setName('Calibri')->setSize(10);
        foreach (self::RIEGO_COLUMNAS as $col => [, $ancho]) {
            $hoja->getColumnDimension($col)->setWidth($ancho);
        }
        $hoja->getPageSetup()->setOrientation(PageSetup::ORIENTATION_LANDSCAPE)->setPaperSize(PageSetup::PAPERSIZE_A4)
            ->setFitToWidth(1)->setFitToHeight(0);
        $hoja->getPageMargins()->setTop(0.4)->setBottom(0.4)->setLeft(0.3)->setRight(0.3);

        // Encabezado: empresa y título
        $empresa = SistemaEmpresaProceso::datos();
        $hoja->mergeCells('A1:J1')->setCellValue('A1', $empresa['razon_social'] ?: 'Empresa sin configurar (Sistema → Configuración → Datos de la empresa)');
        $hoja->mergeCells('A2:J2')->setCellValue('A2', trim(($empresa['ruc'] ? 'RUC ' . $empresa['ruc'] : '') . ($empresa['establecimiento'] ? ' · ' . $empresa['establecimiento'] : '')));
        $hoja->mergeCells('A3:J3')->setCellValue('A3', 'REGISTRO DE TIEMPO DE RIEGO · ' . mb_strtoupper($fecha->translatedFormat('l d \d\e F \d\e Y')));
        $hoja->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $hoja->getStyle('A2')->getFont()->setSize(9)->getColor()->setRGB(self::GRIS);
        $hoja->getStyle('A3')->getFont()->setBold(true)->setSize(11);

        $r = 5;
        if (!$regadores) {
            $hoja->mergeCells("A{$r}:J{$r}")->setCellValue("A{$r}", 'No hay regadores registrados este día.');
            return;
        }
        foreach ($regadores as $k => $regador) {
            if ($k > 0) {
                $hoja->setBreak('A' . ($r - 1), Worksheet::BREAK_ROW);
            }
            $r = $this->bloqueRegador($hoja, $r, $regador) + 2;
        }
        $hoja->setSelectedCell('A1');
    }

    /** Un regador desde la fila $r; devuelve la última fila usada. */
    private function bloqueRegador(Worksheet $hoja, int $r, array $g): int
    {
        $hhmm = fn($h) => $h ? substr($h, 0, 5) : '—';
        $dur = fn(int $min) => $min > 0 ? intdiv($min, 60) . ':' . str_pad((string) ($min % 60), 2, '0', STR_PAD_LEFT) : '0:00';

        // Barra con el nombre
        $hoja->mergeCells("A{$r}:J{$r}")->setCellValue("A{$r}", $g['nombre'] . '   ·   ' . ($g['tipo'] ?? ''));
        $hoja->getStyle("A{$r}:J{$r}")->applyFromArray(['font' => ['bold' => true, 'size' => 12, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => self::NOCHE]]]);
        $hoja->getRowDimension($r)->setRowHeight(24);

        // Datos del día (tres pares etiqueta / valor por fila)
        $almuerzo = $g['almuerzo_inicio'] || $g['almuerzo_fin'] ? $hhmm($g['almuerzo_inicio']) . ' a ' . $hhmm($g['almuerzo_fin']) : 'Sin almuerzo registrado';
        $estado = ($g['es_planilla'] ? ($g['sincronizado'] ? 'Sincronizado con planilla' : 'No sincronizado con planilla') . ' · ' : '')
            . ($g['no_acumular'] ? 'No acumula horas' : 'Acumula horas');
        $info = [
            [['Hora de inicio', $hhmm($g['hora_inicio'])], ['Hora de fin', $hhmm($g['hora_fin'])], ['Almuerzo', $almuerzo]],
            [['Horas de riego', $dur($g['minutos_regados'])], ['Horas de jornal', $dur($g['minutos_jornal'])],
                ['Se acumularon', $g['minutos_acumulados'] > 0 ? $dur($g['minutos_acumulados']) . ' h' : '—']],
            [['Jornal computable', number_format($g['jornal_computable'], 2)], ['Semana / mes', $dur($g['minutos_semana']) . ' / ' . $dur($g['minutos_mes'])],
                ['Estado', $estado]],
        ];
        foreach ($info as $par) {
            $r++;
            foreach ([['A', 'B', 'C', 'C'], ['D', 'E', 'F', 'F'], ['G', 'G', 'H', 'J']] as $i => [$l1, $l2, $v1, $v2]) {
                $l1 !== $l2 && $hoja->mergeCells("{$l1}{$r}:{$l2}{$r}");
                $v1 !== $v2 && $hoja->mergeCells("{$v1}{$r}:{$v2}{$r}");
                $hoja->setCellValue("{$l1}{$r}", $par[$i][0])->setCellValueExplicit("{$v1}{$r}", $par[$i][1], DataType::TYPE_STRING);
                $hoja->getStyle("{$l1}{$r}")->getFont()->getColor()->setRGB(self::GRIS);
                $hoja->getStyle("{$v1}{$r}")->getFont()->setBold(true);
            }
        }

        // Tabla de actividades (las columnas de la pantalla)
        $r += 2;
        $inicioTabla = $r;
        foreach (self::RIEGO_COLUMNAS as $col => [$titulo]) {
            $hoja->setCellValue("{$col}{$r}", $titulo);
        }
        $hoja->getStyle("A{$r}:J{$r}")->applyFromArray(['font' => ['bold' => true, 'color' => ['rgb' => '60646C']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => self::NIEBLA]],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true]]);
        $hoja->getRowDimension($r)->setRowHeight(28);

        $totalHoras = 0;
        $totalJornal = 0;
        foreach ($g['detalles'] as $n => $d) {
            $r++;
            $horas = $this->horasEntre($d['inicio'], $d['fin']);
            $totalHoras += $horas ?? 0;
            $totalJornal += $d['horas_sistema'];
            $hoja->setCellValue("A{$r}", $n + 1);
            $hoja->setCellValue("B{$r}", $d['campo']);
            $this->escribirHora($hoja, "C{$r}", $d['inicio']);
            $this->escribirHora($hoja, "D{$r}", $d['fin']);
            $this->escribirDuracion($hoja, "E{$r}", $horas);
            $hoja->setCellValue("F{$r}", $d['labor']);
            $hoja->setCellValue("G{$r}", $d['descripcion']);
            $hoja->setCellValue("H{$r}", $d['sin_horas'] ? 'Sí' : '');
            $hoja->setCellValue("I{$r}", $d['otro_dia'] ? 'Sí' : '');
            $hoja->setCellValue("J{$r}", round($d['horas_sistema'], 2));
            $hoja->getStyle("J{$r}")->getNumberFormat()->setFormatCode('0.00');
            if ($d['es_riego']) {
                $hoja->getStyle("F{$r}")->getFont()->setBold(true);
            }
            if ($d['sin_horas']) {
                $hoja->getStyle("A{$r}:J{$r}")->getFont()->getColor()->setRGB(self::GRIS);
            }
        }
        if (!$g['detalles']) {
            $r++;
            $hoja->mergeCells("A{$r}:J{$r}")->setCellValue("A{$r}", 'Sin actividades registradas.');
            $hoja->getStyle("A{$r}")->getFont()->getColor()->setRGB(self::GRIS);
        }

        // Totales
        $r++;
        $hoja->mergeCells("A{$r}:D{$r}")->setCellValue("A{$r}", 'Total');
        $this->escribirDuracion($hoja, "E{$r}", $totalHoras);
        $hoja->setCellValue("J{$r}", round($totalJornal, 2));
        $hoja->getStyle("J{$r}")->getNumberFormat()->setFormatCode('0.00');
        $hoja->getStyle("A{$r}:J{$r}")->getFont()->setBold(true);
        $hoja->getStyle("A{$r}:J{$r}")->getBorders()->getTop()->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB('B9BBC6');

        $hoja->getStyle("A{$inicioTabla}:J{$r}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB('E4E5EB');
        $hoja->getStyle("A" . ($inicioTabla + 1) . ":E{$r}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $hoja->getStyle("H" . ($inicioTabla + 1) . ":J{$r}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $hoja->getStyle("F" . ($inicioTabla + 1) . ":G{$r}")->getAlignment()->setWrapText(true)->setVertical(Alignment::VERTICAL_CENTER);

        // Horas acumuladas de otros días que se usaron hoy
        if ($g['acumulado_usado']) {
            $r++;
            $hoja->mergeCells("A{$r}:J{$r}")->setCellValue("A{$r}", 'Se usaron ' . number_format((float) $g['acumulado_usado']['horas'], 2)
                . ' hora(s) de trabajos acumulados' . ($g['acumulado_usado']['origenes'] ? ' de: ' . implode(', ', $g['acumulado_usado']['origenes']) : '') . '.');
            $hoja->getStyle("A{$r}")->getFont()->setItalic(true)->getColor()->setRGB(self::GRIS);
        }
        return $r;
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

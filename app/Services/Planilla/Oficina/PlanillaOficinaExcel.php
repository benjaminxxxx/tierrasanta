<?php

namespace App\Services\Planilla\Oficina;

use App\Models\PlanMensual;
use App\Models\PlanOficinaPersonal;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * Excel de la planilla oficina (sin plantilla: se arma aquí), con las mismas hojas que la planilla oficial:
 * - ADM: lo que se paga a cada persona (blanco a su cuenta, negro a la secundaria o RxH, retenciones de beneficios)
 *   y el costo administrativo del mes.
 * - EMPLEADOS: el cálculo completo por persona, con fórmulas en los totales y una leyenda de cada columna.
 * - BENEFICIOS: el cálculo de CTS y gratificación de cada uno, cómo se le pagan y lo acumulado del semestre.
 * Los montos puestos a mano van resaltados con una nota que dice que no son fórmula y cuánto calculaba el sistema.
 */
class PlanillaOficinaExcel
{
    private const FORMATO = '#,##0.00';
    private const COLOR_ENCABEZADO = '1F4E78';
    private const COLOR_GRUPO = 'D9E1F2';
    private const COLOR_AJUSTE = 'FFF2CC';
    private const COLOR_TOTAL = 'E2EFDA';

    /**
     * Costo administrativo como la hoja ADM: blanco = neto (planilla u honorarios) + lo que corresponde de CTS y
     * gratificación al mes; negro = lo pagado por fuera. También por tipo de ingreso.
     */
    public static function resumenAdm(Collection $filas): array
    {
        $blanco = fn($f) => (float) $f->neto_planilla + (float) $f->provision_cts + (float) $f->provision_gratificacion;
        $grupos = [];
        foreach (['planilla' => 'En planilla', 'honorarios' => 'Recibo por honorarios'] as $tipo => $nombre) {
            $g = $filas->where('tipo_ingreso', $tipo);
            if ($g->isNotEmpty()) {
                $grupos[$nombre] = ['blanco' => round($g->sum($blanco), 2), 'negro' => round($g->sum(fn($f) => (float) $f->bonificacion_negro), 2)];
            }
        }
        return [
            'blanco' => round($filas->sum($blanco), 2),
            'negro' => round($filas->sum(fn($f) => (float) $f->bonificacion_negro), 2),
            'neto' => round($filas->sum(fn($f) => (float) $f->neto_planilla), 2),
            'provisiones' => round($filas->sum(fn($f) => (float) $f->provision_cts + (float) $f->provision_gratificacion), 2),
            'pagado_blanco' => round($filas->sum(fn($f) => (float) $f->pago_blanco_mes), 2),
            'contable' => round($filas->sum(fn($f) => (float) $f->costo_contable), 2),
            'total' => round($filas->sum(fn($f) => (float) $f->costo_total), 2),
            'grupos' => $grupos,
        ];
    }

    /** @return string ruta en el disco public */
    public function generar(PlanMensual $plan): string
    {
        $filas = PlanOficinaPersonal::where('plan_mensual_id', $plan->id)->orderBy('orden')->get();
        $periodo = mb_strtoupper(Carbon::create($plan->anio, $plan->mes, 1)->translatedFormat('F Y'));

        $libro = new Spreadsheet();
        $libro->getDefaultStyle()->getFont()->setName('Calibri')->setSize(10);
        $this->hojaAdm($libro->getActiveSheet(), $filas, $plan, $periodo);
        $this->hojaEmpleados($libro->createSheet(), $filas, $plan, $periodo);
        $this->hojaBeneficios($libro->createSheet(), $filas, $plan, $periodo);
        $libro->setActiveSheetIndex(0);

        $carpeta = sprintf('planilla/%04d-%02d', $plan->anio, $plan->mes);
        $ruta = "{$carpeta}/Planilla_Oficina_{$plan->mes}_{$plan->anio}.xlsx";
        Storage::disk('public')->makeDirectory($carpeta);
        (new Xlsx($libro))->save(Storage::disk('public')->path($ruta));
        return $ruta;
    }

    // ------------------------------------------------------------------ ADM

    private function hojaAdm(Worksheet $h, Collection $filas, PlanMensual $plan, string $periodo): void
    {
        $h->setTitle('ADM');
        $this->cabecera($h, 'PLANILLA OFICINA — PAGOS Y COSTO ADMINISTRATIVO', $periodo, 'K');

        $cols = ['Nº', 'Nombres y apellidos', 'Forma de pago', 'Neto planilla / honorarios (blanco)', 'Cuenta blanco',
            'Neto bonificación (negro)', 'Cuenta negro / comprobante', 'Neto por pagar', 'Retener CTS', 'Retener gratificación', 'General'];
        $f = 6;
        $this->encabezados($h, $f, $cols);
        $f++;
        $filasTotales = [];
        $n = 0;
        foreach (['honorarios' => 'Recibo por honorarios', 'planilla' => 'En planilla'] as $tipo => $nombre) {
            $grupo = $filas->where('tipo_ingreso', $tipo);
            if ($grupo->isEmpty()) {
                continue;
            }
            $h->setCellValue("A{$f}", mb_strtoupper($nombre));
            $this->estiloGrupo($h, "A{$f}:K{$f}");
            $f++;
            $inicio = $f;
            foreach ($grupo as $p) {
                $h->setCellValue("A{$f}", ++$n);
                $h->setCellValue("B{$f}", $p->nombres);
                $h->setCellValue("C{$f}", $p->esHonorarios() ? 'RxH' : ($p->beneficios_mensuales ? 'Planilla (beneficios cada mes)' : 'Planilla'));
                $h->setCellValue("D{$f}", (float) $p->neto_planilla);
                $h->setCellValue("E{$f}", $p->cuenta_principal);
                $h->setCellValue("F{$f}", (float) $p->bonificacion_negro);
                $h->setCellValue("G{$f}", $p->esHonorarios() ? trim('RxH ' . ($p->comprobante ?? '')) : $p->cuenta_secundaria);
                $h->setCellValue("H{$f}", "=D{$f}+F{$f}");
                $h->setCellValue("I{$f}", (float) $p->provision_cts);
                $h->setCellValue("J{$f}", (float) $p->provision_gratificacion);
                $h->setCellValue("K{$f}", "=H{$f}+I{$f}+J{$f}");
                $f++;
            }
            $h->setCellValue("B{$f}", 'Subtotal ' . mb_strtolower($nombre));
            foreach (['D', 'F', 'H', 'I', 'J', 'K'] as $c) {
                $h->setCellValue("{$c}{$f}", "=SUM({$c}{$inicio}:{$c}" . ($f - 1) . ')');
            }
            $this->estiloTotal($h, "A{$f}:K{$f}");
            $filasTotales[] = $f;
            $f += 2;
        }
        $h->setCellValue("B{$f}", 'TOTAL');
        foreach (['D', 'F', 'H', 'I', 'J', 'K'] as $c) {
            $h->setCellValue("{$c}{$f}", '=' . implode('+', array_map(fn($t) => "{$c}{$t}", $filasTotales ?: [0])));
        }
        $this->estiloTotal($h, "A{$f}:K{$f}", true);
        $total = $f;

        // Costo administrativo del mes (lo que se compara con Costos mensuales)
        $f += 2;
        $h->setCellValue("B{$f}", 'COSTO ADMINISTRATIVO DEL MES');
        $h->getStyle("B{$f}")->getFont()->setBold(true);
        $f++;
        $h->setCellValue("B{$f}", 'Blanco (neto + retenciones de CTS y gratificación)');
        $h->setCellValue("D{$f}", "=D{$total}+I{$total}+J{$total}");
        $f++;
        $h->setCellValue("B{$f}", 'Negro (bonificación por fuera)');
        $h->setCellValue("D{$f}", "=F{$total}");
        $f++;
        $h->setCellValue("B{$f}", 'Total');
        $h->setCellValue("D{$f}", '=D' . ($f - 2) . '+D' . ($f - 1));
        $h->getStyle("B{$f}:D{$f}")->getFont()->setBold(true);
        $f += 2;
        $h->setCellValue("B{$f}", 'Retener CTS / gratificación: lo que corresponde al mes. Con beneficios en dos tramos se guarda y se paga en mayo/noviembre (CTS) y julio/diciembre (gratificación); con beneficios cada mes se paga junto al sueldo.');
        $h->getStyle("B{$f}")->getFont()->setItalic(true)->setSize(9);

        $h->getStyle("D7:D{$f}")->getNumberFormat()->setFormatCode(self::FORMATO);
        $h->getStyle("F7:F{$f}")->getNumberFormat()->setFormatCode(self::FORMATO);
        $h->getStyle("H7:K{$f}")->getNumberFormat()->setFormatCode(self::FORMATO);
        $this->anchos($h, ['A' => 5, 'B' => 42, 'C' => 16, 'D' => 16, 'E' => 30, 'F' => 16, 'G' => 30, 'H' => 15, 'I' => 13, 'J' => 13, 'K' => 15]);
        $h->freezePane('C7');
    }

    // ------------------------------------------------------------------ EMPLEADOS

    private function hojaEmpleados(Worksheet $h, Collection $filas, PlanMensual $plan, string $periodo): void
    {
        $h->setTitle('EMPLEADOS');
        // [columna, título, campo o fórmula ('=' con {r}), grupo]
        $columnas = [
            ['A', 'Nº', null, 'DATOS'], ['B', 'Apellidos y nombres', 'nombres', 'DATOS'], ['C', 'Cargo', 'cargo', 'DATOS'],
            ['D', 'Forma', null, 'DATOS'], ['E', 'Pensión', 'sistema_pension', 'DATOS'],
            ['F', 'Días lab.', 'dias_laborados', 'DÍAS'], ['G', 'Vacac.', 'dias_vacaciones', 'DÍAS'], ['H', 'Faltas', 'dias_suspension_perfecta', 'DÍAS'],
            ['I', 'Sueldo / honorarios', 'rem_sueldo', 'REMUNERACIONES'], ['J', 'Vacaciones 0118', 'rem_vacaciones', 'REMUNERACIONES'],
            ['K', 'Asig. familiar 0201', 'rem_asignacion_familiar', 'REMUNERACIONES'], ['L', 'Total remun.', '=I{r}+J{r}+K{r}', 'REMUNERACIONES'],
            ['M', 'AFP fondo', 'desc_afp_fondo', 'DESCUENTOS'], ['N', 'Comisión AFP', 'desc_afp_comision', 'DESCUENTOS'],
            ['O', 'Prima AFP', 'desc_afp_prima', 'DESCUENTOS'], ['P', 'SNP', 'desc_snp', 'DESCUENTOS'],
            ['Q', '5ta categ.', 'desc_renta_quinta', 'DESCUENTOS'], ['R', '4ta categ.', 'desc_renta_cuarta', 'DESCUENTOS'],
            ['S', 'Total descuentos', '=SUM(M{r}:R{r})', 'DESCUENTOS'],
            ['T', 'Neto a pagar', '=L{r}-S{r}', 'NETO'],
            ['U', 'EsSalud', 'aporte_essalud', 'APORTES'], ['V', 'Vida ley', 'aporte_vida_ley', 'APORTES'],
            ['W', 'CTS del mes (retener)', 'provision_cts', 'BENEFICIOS'], ['X', 'Gratif. del mes (retener)', 'provision_gratificacion', 'BENEFICIOS'],
            ['Y', 'Gratificación pagada 406', 'gratificacion', 'BENEFICIOS'], ['Z', 'Bonif. extraord. 312', 'bonif_extraordinaria', 'BENEFICIOS'],
            ['AA', 'CTS pagada 904', 'cts', 'BENEFICIOS'], ['AB', 'Beneficios pagados en el mes', 'beneficios_pagados', 'BENEFICIOS'],
            ['AC', 'Sueldo real', 'sueldo_real', 'REAL'], ['AD', 'Bonificación (negro)', '=IF(AC{r}="",0,AC{r}-T{r}-W{r}-X{r})', 'REAL'],
            ['AE', 'Pagado en blanco este mes', '=T{r}+AB{r}', 'REAL'],
            ['AF', 'Costo contable', '=L{r}+U{r}+V{r}+AB{r}+Q{r}', 'COSTOS'], ['AG', 'Costo B+N', '=IF(AC{r}="",T{r},AC{r})+U{r}+V{r}+Q{r}', 'COSTOS'],
        ];
        $ultima = 'AG';
        $this->cabecera($h, 'PLANILLA OFICINA — RÉGIMEN GENERAL', $periodo, $ultima);
        $h->setCellValue('A4', sprintf('EsSalud %s%% · vida ley %s%% × 1.18 · bonificación extraordinaria %s%% · retención 4ta %s%% (desde S/ %s) · RMV S/ %s',
            $this->num($plan->essalud_general), $this->num($plan->vida_ley), $this->num($plan->bonif_extraordinaria_general),
            $this->num($plan->retencion_cuarta), number_format((float) $plan->tope_retencion_cuarta, 0), number_format((float) $plan->rmv, 2)));
        $h->getStyle('A4')->getFont()->setItalic(true)->setSize(9);

        // Fila de grupos (combinada) y encabezados
        $fg = 6;
        $grupos = [];
        foreach ($columnas as [$c, , , $g]) {
            $grupos[$g][] = $c;
        }
        foreach ($grupos as $g => $cs) {
            $rango = $cs[0] . $fg . ':' . end($cs) . $fg;
            $h->setCellValue($cs[0] . $fg, $g);
            if (count($cs) > 1) {
                $h->mergeCells($rango);
            }
            $this->estiloGrupo($h, $rango, true);
        }
        $this->encabezados($h, $fg + 1, array_column($columnas, 1));

        $f = $fg + 2;
        $inicio = $f;
        foreach ($filas as $i => $p) {
            foreach ($columnas as [$c, , $campo]) {
                $celda = "{$c}{$f}";
                if ($c === 'A') {
                    $h->setCellValue($celda, $i + 1);
                } elseif ($c === 'D') {
                    $h->setCellValue($celda, $p->esHonorarios() ? 'RxH' : ($p->beneficios_mensuales ? 'Planilla · benef. mensual' : 'Planilla'));
                } elseif (str_starts_with((string) $campo, '=')) {
                    $h->setCellValue($celda, str_replace('{r}', (string) $f, $campo));
                } else {
                    $valor = $p->{$campo};
                    $h->setCellValue($celda, in_array($campo, PlanOficinaPersonal::MONTOS, true) ? ($valor === null ? null : (float) $valor) : $valor);
                    $this->marcarAjuste($h, $celda, $p, $campo);
                }
            }
            $f++;
        }
        $fin = $f - 1;
        $h->setCellValue("B{$f}", 'TOTAL');
        foreach ($columnas as [$c, , $campo]) {
            if ($c >= 'F' || strlen($c) > 1) {
                $h->setCellValue("{$c}{$f}", $filas->isEmpty() ? 0 : "=SUM({$c}{$inicio}:{$c}{$fin})");
            }
        }
        $this->estiloTotal($h, "A{$f}:{$ultima}{$f}", true);
        $h->getStyle("I{$inicio}:{$ultima}{$f}")->getNumberFormat()->setFormatCode(self::FORMATO);
        $h->getStyle("F{$inicio}:H{$f}")->getNumberFormat()->setFormatCode('0');
        $this->bordes($h, "A" . ($fg + 1) . ":{$ultima}{$f}");

        // Leyenda: cómo sale cada columna
        $f += 2;
        $leyenda = [
            'Sueldo' => 'Remuneración básica del contrato (o la RMV) menos vacaciones y faltas a sueldo / 30 por día. Honorarios: el monto del recibo.',
            'Vacaciones 0118' => 'Sueldo / 30 × días de descanso vacacional (suspensión 23).',
            'Asig. familiar' => '10% de la RMV si tiene hijos con derecho; se paga completa.',
            'AFP / SNP' => 'Tasas del mes sobre el total de remuneraciones (fondo, comisión, prima; mayores de 65 sin prima). SNP 13%. Pensionistas no aportan.',
            '5ta / 4ta' => '5ta categoría: a mano. 4ta (honorarios): 8% si el recibo pasa de S/ 1,500, salvo suspensión de retenciones.',
            'EsSalud / vida ley' => 'EsSalud del régimen general sobre el total de remuneraciones; vida ley = total × tasa × 1.18.',
            'CTS del mes' => '(Remuneración + asignación + 1/6 de gratificación) / 12.',
            'Gratificación del mes' => '(Remuneración + asignación) × (1 + bonificación extraordinaria) / 6.',
            'Pagadas' => 'Dos tramos: gratificación + bonificación en julio y diciembre, CTS en mayo y noviembre. Beneficios cada mes: lo del mes junto al sueldo.',
            'Bonificación (negro)' => 'Sueldo real − neto − CTS del mes − gratificación del mes (lo que se paga por fuera a la cuenta secundaria).',
            'Costo contable' => 'Remuneraciones + EsSalud + vida ley + beneficios pagados en el mes + 5ta.',
            'Costo B+N' => 'Sueldo real + EsSalud + vida ley + 5ta.',
            'Celdas amarillas' => 'Montos puestos a mano (no son fórmula): la nota dice el motivo y cuánto calculaba el sistema.',
        ];
        $h->setCellValue("B{$f}", 'CÓMO SE CALCULA');
        $h->getStyle("B{$f}")->getFont()->setBold(true);
        foreach ($leyenda as $titulo => $texto) {
            $f++;
            $h->setCellValue("B{$f}", $titulo);
            $h->setCellValue("C{$f}", $texto);
            $h->getStyle("B{$f}")->getFont()->setBold(true);
        }
        $h->getStyle("B" . ($f - count($leyenda)) . ":C{$f}")->getFont()->setSize(9);
        $this->anchos($h, ['A' => 5, 'B' => 38, 'C' => 18, 'D' => 14, 'E' => 9] + array_fill_keys(['F', 'G', 'H'], 7));
        foreach (range(9, Coordinate::columnIndexFromString($ultima)) as $i) {
            $h->getColumnDimension(Coordinate::stringFromColumnIndex($i))->setWidth(13);
        }
        $h->getStyle("A" . ($fg + 1) . ":{$ultima}" . ($fg + 1))->getAlignment()->setWrapText(true);
        $h->freezePane('C' . ($fg + 2));
    }

    // ------------------------------------------------------------------ BENEFICIOS

    private function hojaBeneficios(Worksheet $h, Collection $filas, PlanMensual $plan, string $periodo): void
    {
        $h->setTitle('BENEFICIOS');
        $this->cabecera($h, 'CTS Y GRATIFICACIÓN — CÁLCULO Y FORMA DE PAGO', $periodo, 'N');
        $mes = (int) $plan->mes;
        $anio = (int) $plan->anio;
        $bonif = (float) ($plan->bonif_extraordinaria_general ?? 9) / 100;

        $cols = ['Nº', 'Apellidos y nombres', 'Forma de pago', 'Remuneración', 'Asig. familiar', 'Remuneración computable',
            '1/6 gratificación', 'CTS del mes', 'Gratificación del mes', 'Próxima CTS', 'CTS acumulada del semestre',
            'Próxima gratificación', 'Gratificación acumulada del semestre', 'Pagado este mes'];
        $this->encabezados($h, 6, $cols);
        $f = 7;
        $inicio = $f;
        $mesesCts = $this->mesesTranscurridos($mes, [5, 11]);
        $mesesGrati = $this->mesesTranscurridos($mes, [7, 12]);
        foreach ($filas->where('tipo_ingreso', 'planilla')->values() as $i => $p) {
            $h->setCellValue("A{$f}", $i + 1);
            $h->setCellValue("B{$f}", $p->nombres);
            $h->setCellValue("C{$f}", $p->beneficios_mensuales ? 'Cada mes con el sueldo' : 'Dos tramos');
            $h->setCellValue("D{$f}", (float) $p->remuneracion_basica);
            $h->setCellValue("E{$f}", (float) $p->asignacion_familiar);
            $h->setCellValue("F{$f}", "=D{$f}+E{$f}");
            $h->setCellValue("G{$f}", "=F{$f}/6");
            $h->setCellValue("H{$f}", (float) $p->provision_cts);
            $h->setCellValue("I{$f}", (float) $p->provision_gratificacion);
            $this->marcarAjuste($h, "H{$f}", $p, 'provision_cts');
            $this->marcarAjuste($h, "I{$f}", $p, 'provision_gratificacion');
            $h->setCellValue("J{$f}", $p->beneficios_mensuales ? '— (se paga cada mes)' : $this->proximoPago($mes, $anio, [5, 11]));
            $h->setCellValue("K{$f}", $p->beneficios_mensuales ? 0 : "=H{$f}*{$mesesCts}");
            $h->setCellValue("L{$f}", $p->beneficios_mensuales ? '— (se paga cada mes)' : $this->proximoPago($mes, $anio, [7, 12]));
            $h->setCellValue("M{$f}", $p->beneficios_mensuales ? 0 : "=I{$f}*{$mesesGrati}");
            $h->setCellValue("N{$f}", (float) $p->beneficios_pagados);
            $f++;
        }
        $h->setCellValue("B{$f}", 'TOTAL');
        foreach (['D', 'E', 'F', 'G', 'H', 'I', 'K', 'M', 'N'] as $c) {
            $h->setCellValue("{$c}{$f}", $f > $inicio ? "=SUM({$c}{$inicio}:{$c}" . ($f - 1) . ')' : 0);
        }
        $this->estiloTotal($h, "A{$f}:N{$f}", true);
        $h->getStyle("D{$inicio}:N{$f}")->getNumberFormat()->setFormatCode(self::FORMATO);
        $this->bordes($h, "A6:N{$f}");

        $f += 2;
        $notas = [
            'CTS del mes = (remuneración computable + 1/6 de gratificación) / 12. Se deposita en mayo (nov–abr) y noviembre (may–oct).',
            'Gratificación del mes = remuneración computable × ' . number_format(1 + $bonif, 2) . ' / 6 (incluye bonificación extraordinaria). Se paga en julio (ene–jun) y diciembre (jul–dic).',
            'Acumulado del semestre: lo que corresponde desde el inicio del semestre hasta este mes inclusive (CTS: ' . $mesesCts . ' mes(es); gratificación: ' . $mesesGrati . ' mes(es)).',
            'Cada mes con el sueldo: el trabajador lo recibe mes a mes y no hay pago aparte. Según la norma, esto solo libera a la empresa en casos como la remuneración integral anual; confirmar con contabilidad.',
            'Recibo por honorarios: no tiene CTS ni gratificación.',
        ];
        foreach ($notas as $nota) {
            $h->setCellValue("B{$f}", $nota);
            $h->getStyle("B{$f}")->getFont()->setItalic(true)->setSize(9);
            $f++;
        }
        $this->anchos($h, ['A' => 5, 'B' => 38, 'C' => 22, 'D' => 13, 'E' => 12, 'F' => 14, 'G' => 13, 'H' => 13, 'I' => 14, 'J' => 18, 'K' => 15, 'L' => 18, 'M' => 16, 'N' => 14]);
        $h->getStyle('A6:N6')->getAlignment()->setWrapText(true);
        $h->freezePane('C7');
    }

    // ------------------------------------------------------------------ apoyo

    private function cabecera(Worksheet $h, string $titulo, string $periodo, string $ultima): void
    {
        $h->setCellValue('A1', 'TIERRA SANTA HOLDING S.A.C.');
        $h->setCellValue('A2', $titulo);
        $h->setCellValue('A3', "Periodo: {$periodo} · Tipo: OFICINA");
        $h->getStyle('A1:A2')->getFont()->setBold(true)->setSize(12);
        $h->getStyle('A3')->getFont()->setBold(true);
    }

    private function encabezados(Worksheet $h, int $fila, array $titulos): void
    {
        foreach (array_values($titulos) as $i => $t) {
            $h->setCellValue(Coordinate::stringFromColumnIndex($i + 1) . $fila, $t);
        }
        $rango = "A{$fila}:" . Coordinate::stringFromColumnIndex(count($titulos)) . $fila;
        $h->getStyle($rango)->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
        $h->getStyle($rango)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB(self::COLOR_ENCABEZADO);
        $h->getStyle($rango)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER)->setWrapText(true);
        $h->getRowDimension($fila)->setRowHeight(32);
    }

    private function estiloGrupo(Worksheet $h, string $rango, bool $centrado = false): void
    {
        $h->getStyle($rango)->getFont()->setBold(true);
        $h->getStyle($rango)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB(self::COLOR_GRUPO);
        if ($centrado) {
            $h->getStyle($rango)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        }
    }

    private function estiloTotal(Worksheet $h, string $rango, bool $fuerte = false): void
    {
        $h->getStyle($rango)->getFont()->setBold(true);
        $h->getStyle($rango)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB($fuerte ? 'C6E0B4' : self::COLOR_TOTAL);
    }

    private function bordes(Worksheet $h, string $rango): void
    {
        $h->getStyle($rango)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB('BFBFBF');
    }

    private function anchos(Worksheet $h, array $anchos): void
    {
        foreach ($anchos as $c => $w) {
            $h->getColumnDimension($c)->setWidth($w);
        }
    }

    /** Monto puesto a mano: valor fijo, resaltado, con nota. */
    private function marcarAjuste(Worksheet $h, string $celda, PlanOficinaPersonal $p, string $campo): void
    {
        $ajuste = $p->ajuste($campo);
        if (!$ajuste) {
            return;
        }
        $h->getStyle($celda)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB(self::COLOR_AJUSTE);
        $nota = $h->getComment($celda);
        $nota->setWidth('260pt')->setHeight('70pt');
        $nota->getText()->createTextRun("MONTO PERSONALIZADO (no es fórmula).\n"
            . (($ajuste['motivo'] ?? null) ? "Motivo: {$ajuste['motivo']}\n" : '')
            . 'Calculado por el sistema: S/ ' . number_format((float) ($p->calculado($campo) ?? 0), 2));
    }

    private function num($v): string
    {
        return rtrim(rtrim(number_format((float) $v, 2, '.', ''), '0'), '.');
    }

    /** Meses del semestre de pago transcurridos hasta este mes inclusive (para lo acumulado). */
    private function mesesTranscurridos(int $mes, array $mesesPago): int
    {
        // El semestre de cada pago termina el mes anterior (CTS) o el mismo mes (gratificación)
        $esGrati = $mesesPago === [7, 12];
        foreach ($mesesPago as $pago) {
            $finSem = $esGrati ? $pago : $pago - 1;
            $iniSem = $finSem - 5;
            for ($k = 0; $k < 6; $k++) {
                $m = (($iniSem + $k - 1) % 12 + 12) % 12 + 1;
                if ($m === $mes) {
                    return $k + 1;
                }
            }
        }
        return 0;
    }

    private function proximoPago(int $mes, int $anio, array $mesesPago): string
    {
        foreach ([0, 1] as $sumaAnio) {
            foreach ($mesesPago as $m) {
                if ($sumaAnio || $m >= $mes) {
                    return ucfirst(Carbon::create($anio + $sumaAnio, $m, 1)->translatedFormat('F Y'));
                }
            }
        }
        return '';
    }
}

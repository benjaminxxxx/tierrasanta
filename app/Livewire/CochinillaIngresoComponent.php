<?php

namespace App\Livewire;

use App\Models\CampoCampania;
use App\Models\CochinillaIngreso;
use App\Models\CochinillaObservacion;
use App\Models\CochinillaVenteado;
use App\Services\AuditoriaServicio;
use App\Services\CochinillaIngresoServicio;
use App\Support\ExcelHelper;
use DB;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;
use Livewire\WithPagination;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
//MODULO COCHINILLA INGRESO
class CochinillaIngresoComponent extends Component
{
    use LivewireAlert;
    use WithPagination;
    public $campoSeleccionado;
    public $campaniaSeleccionado;
    public $observacionSeleccionado;
    public $lote;
    public $anioSeleccionado;
    public $filtroVenteado;
    public $filtroFiltrado;
    public $aniosDisponibles = [];
    public $observaciones = [];
    protected $listeners = [
        'cochinillaIngresado',
        'detalleIngresoAgregado' => '$refresh',
        "venteadoAgregado" => '$refresh',
        "filtradoAgregado" => '$refresh',
        'eliminarIngresoConfirmado'
    ];
    public function mount()
    {
        $this->observaciones = CochinillaObservacion::all();
    }

    public function cochinillaIngresado()
    {
        $this->resetPage();
    }
    public function updatedCampoSeleccionado()
    {
        $this->resetPage();
    }
    public function updatedCampaniaSeleccionado()
    {
        $this->resetPage();
    }
    public function updatedObservacionSeleccionado()
    {
        $this->resetPage();
    }
    public function updatedFiltroVenteado()
    {
        $this->resetPage();
    }
    public function updatedFiltroFiltrado()
    {
        $this->resetPage();
    }
    public function eliminarIngresoConfirmado($data)
    {
        $ingreso = CochinillaIngreso::find($data['laborId']);
        if ($ingreso) {
            $data = $ingreso->toArray();
            $data['eliminado_por'] = auth()->user()->name;
            $data['detalles'] = $ingreso->detalles->toArray();
            $data['venteados'] = $ingreso->venteados->toArray();
            $data['filtrados'] = $ingreso->filtrados->toArray();

            AuditoriaServicio::registrar(
                modelo: CochinillaIngreso::class,
                modeloId: $ingreso->id,
                accion: 'eliminar',
                antes: $data,
                camposIgnorados: [
                    'created_at',
                    'updated_at',
                    // --- Campos de Venteado ---
                    'venteado_kilos_ingresados',
                    'venteado_limpia',
                    'venteado_basura',
                    'venteado_polvillo',
                    'venteado_limpia_porcentaje',
                    'venteado_basura_porcentaje',
                    'venteado_polvillo_porcentaje',
                    'venteado_diferencia_kilos',
                    'venteado_diferencia_porcentaje',
                    // --- Campos de Filtrado ---
                    'filtrado_kilos_ingresados',
                    'filtrado_primera',
                    'filtrado_segunda',
                    'filtrado_tercera',
                    'filtrado_piedra',
                    'filtrado_basura',
                    'filtrado_primera_porcentaje',
                    'filtrado_segunda_porcentaje',
                    'filtrado_tercera_porcentaje',
                    'filtrado_piedra_porcentaje',
                    'filtrado_basura_porcentaje',
                    'filtrado_diferencia_kilos',
                    'filtrado_diferencia_porcentaje',
                    // --- Stock y Totales ---
                    'stock_disponible',
                    'total_filtrado_kilos',
                    'total_filtrado_primera',
                    'total_filtrado_segunda',
                    'total_filtrado_tercera',
                    'total_filtrado_piedra',
                    'total_filtrado_basura',
                    'total_filtrado_total',
                ],
            );

            $ingreso->venteados()->delete();
            $ingreso->filtrados()->delete();
            $ingreso->delete();
            $this->alert('success', 'Ingreso eliminado correctamente.');
        } else {
            $this->alert('error', 'Ingreso no encontrado.');
        }
    }
    public function eliminarIngreso($ingresoId)
    {
        $this->confirm('¿Está seguro de eliminar este ingreso?', [
            'onConfirmed' => 'eliminarIngresoConfirmado',
            'data' => [
                'laborId' => $ingresoId,
            ],
        ]);


    }
    /**
     * Busca una hoja por nombre sin sensibilidad a mayúsculas/minúsculas
     * (getSheetByName() de PhpSpreadsheet SÍ distingue mayúsculas).
     */
    private function buscarHojaPorNombre($spreadsheet, string $nombre)
    {
        foreach ($spreadsheet->getAllSheets() as $hoja) {
            if (strcasecmp($hoja->getTitle(), $nombre) === 0) {
                return $hoja;
            }
        }
        return null;
    }
    public function exportarExcel()
    {
        try {
            $spreadsheet = ExcelHelper::cargarPlantilla('rpt_tmpl_reporte_cochinilla_ingreso.xlsx');

            $hojaIngreso = $this->buscarHojaPorNombre($spreadsheet, 'ingreso');
            $hojaVenteado = $this->buscarHojaPorNombre($spreadsheet, 'venteado');
            $hojaFiltrado = $this->buscarHojaPorNombre($spreadsheet, 'filtrado');

            if (!$hojaIngreso || !$hojaVenteado || !$hojaFiltrado) {
                throw new \Exception('La plantilla debe contener las hojas "ingreso", "Venteado" y "Filtrado".');
            }

            // Respeta los mismos filtros que la tabla principal
            $query = $this->construirQueryFiltrada()
                ->with([
                    'detalles.observacionRelacionada',
                    'campoCampania',
                    'observacionRelacionada',
                    'venteados',
                    'filtrados',
                ]);

            if ($this->anioSeleccionado) {
                $query->whereYear('fecha', $this->anioSeleccionado);
            }

            $ingresos = $query->orderBy('lote')->get();

            $filaIngreso = 8;
            $filaVenteado = 8;
            $filaFiltrado = 8;

            foreach ($ingresos as $ingreso) {

                // ---------- SUMATORIAS DE VENTEADO ----------
                $vIngresado = (float) $ingreso->venteados->sum('kilos_ingresado');
                $vLimpia = (float) $ingreso->venteados->sum('limpia');
                $vBasura = (float) $ingreso->venteados->sum('basura');   // asume columna 'basura' en venteados
                $vPolvillo = (float) $ingreso->venteados->sum('polvillo');
                $vTotal = $vLimpia + $vBasura + $vPolvillo;
                $fechaSarandeada = $ingreso->venteados->max('fecha_proceso');

                // ---------- SUMATORIAS DE FILTRADO ----------
                $fIngresado = (float) $ingreso->filtrados->sum('kilos_ingresados');
                $fPrimera = (float) $ingreso->filtrados->sum('primera');
                $fSegunda = (float) $ingreso->filtrados->sum('segunda');
                $fTercera = (float) $ingreso->filtrados->sum('tercera');
                $fPiedra = (float) $ingreso->filtrados->sum('piedra');
                $fBasura = (float) $ingreso->filtrados->sum(fn($f) => $f->basura); // accessor calculado
                $fTotal = $fPrimera + $fSegunda + $fTercera + $fPiedra + $fBasura;
                $fechaFiltrado = $ingreso->filtrados->max('fecha_proceso');

                // ---------- PROVEEDOR (KG Expor. / KG por HA) ----------
                $kgExport = $fPrimera + $fSegunda + $fTercera; // primera+segunda+tercera del filtrado
                $kgPorHa = $ingreso->area > 0 ? $kgExport / $ingreso->area : null;

                // ---------- DIFERENCIA FINAL (mostrada en M/N y en AP/AQ) ----------
                $diferenciaFinal = $ingreso->total_kilos - $vTotal;
                $porcentajeDiferenciaFinal = $ingreso->total_kilos > 0
                    ? $diferenciaFinal / $ingreso->total_kilos
                    : null;

                // ================= FILAS DE SUBLOTES =================
                foreach ($ingreso->detalles as $detalle) {
                    $hojaIngreso->setCellValue("B{$filaIngreso}", $detalle->sublote_codigo);
                    $hojaIngreso->setCellValueExplicit(
                        "C{$filaIngreso}",
                        ExcelDate::PHPToExcel($detalle->fecha),
                        \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC
                    );
                    $hojaIngreso->getStyle("C{$filaIngreso}")->getNumberFormat()->setFormatCode('dd/mm/yyyy');
                    $hojaIngreso->setCellValue("K{$filaIngreso}", $detalle->total_kilos);
                    $hojaIngreso->setCellValue("L{$filaIngreso}", $detalle->observacionRelacionada?->descripcion);
                    $this->aplicarEstilosFila($hojaIngreso, $filaIngreso, negrita: false);
                    $filaIngreso++;
                }

                // ================= FILA TOTALIZADA DEL LOTE =================
                $filaLote = $filaIngreso;
                $hojaIngreso->setCellValue("A{$filaLote}", $ingreso->lote);
                $hojaIngreso->setCellValueExplicit(
                    "C{$filaLote}",
                    ExcelDate::PHPToExcel($ingreso->fecha),
                    \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC
                );
                $hojaIngreso->getStyle("C{$filaLote}")->getNumberFormat()->setFormatCode('dd/mm/yyyy');
                $hojaIngreso->setCellValue("D{$filaLote}", $ingreso->campo);
                $hojaIngreso->setCellValue("E{$filaLote}", $ingreso->area);
                $hojaIngreso->setCellValue("F{$filaLote}", $ingreso->campoCampania?->nombre_campania);
                $hojaIngreso->setCellValue("G{$filaLote}", $ingreso->campoCampania?->variedad_tuna);

                if ($ingreso->fecha_siembra) {
                    $hojaIngreso->setCellValueExplicit(
                        "H{$filaLote}",
                        ExcelDate::PHPToExcel($ingreso->fecha_siembra),
                        \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC
                    );
                    $hojaIngreso->getStyle("H{$filaLote}")->getNumberFormat()->setFormatCode('dd/mm/yyyy');
                }

                $hojaIngreso->setCellValue("I{$filaLote}", $kgExport ?: null);
                $hojaIngreso->setCellValue("J{$filaLote}", $kgPorHa);
                $hojaIngreso->setCellValue("K{$filaLote}", $ingreso->total_kilos);
                $hojaIngreso->setCellValue("L{$filaLote}", $ingreso->observacionRelacionada?->descripcion);

                // KILOS FINALES (espejo de AP/AQ)
                $hojaIngreso->setCellValue("M{$filaLote}", $diferenciaFinal);
                $hojaIngreso->setCellValue("N{$filaLote}", $porcentajeDiferenciaFinal);

                // Bloque VENTEADO (solo si el lote tuvo venteado)
                if ($ingreso->venteados->isNotEmpty()) {
                    $hojaIngreso->setCellValue("O{$filaLote}", $vIngresado);
                    $hojaIngreso->setCellValue("P{$filaLote}", $vLimpia);
                    $hojaIngreso->setCellValue("Q{$filaLote}", $vBasura);
                    $hojaIngreso->setCellValue("R{$filaLote}", $vPolvillo);
                    if ($vIngresado > 0) {
                        $hojaIngreso->setCellValue("S{$filaLote}", $vLimpia / $vIngresado);
                        $hojaIngreso->setCellValue("T{$filaLote}", $vBasura / $vIngresado);
                        $hojaIngreso->setCellValue("U{$filaLote}", $vPolvillo / $vIngresado);
                    }
                    $hojaIngreso->setCellValue("V{$filaLote}", $vTotal);
                    $hojaIngreso->setCellValue("W{$filaLote}", abs($vIngresado - $vTotal) < 0.5 ? 'OK' : '');
                    $hojaIngreso->setCellValue("Y{$filaLote}", $vIngresado - $vTotal);

                    if ($fechaSarandeada) {
                        $hojaIngreso->setCellValueExplicit(
                            "AA{$filaLote}",
                            ExcelDate::PHPToExcel($fechaSarandeada),
                            \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC
                        );
                        $hojaIngreso->getStyle("AA{$filaLote}")->getNumberFormat()->setFormatCode('dd/mm/yyyy');
                    }
                }

                // Bloque FILTRADO (solo si el lote tuvo filtrado)
                if ($ingreso->filtrados->isNotEmpty()) {
                    $hojaIngreso->setCellValue("AB{$filaLote}", $fIngresado);
                    $hojaIngreso->setCellValue("AC{$filaLote}", $fPrimera);
                    $hojaIngreso->setCellValue("AD{$filaLote}", $fSegunda);
                    $hojaIngreso->setCellValue("AE{$filaLote}", $fTercera);
                    $hojaIngreso->setCellValue("AF{$filaLote}", $fPiedra);
                    $hojaIngreso->setCellValue("AG{$filaLote}", $fBasura);
                    if ($fIngresado > 0) {
                        $hojaIngreso->setCellValue("AH{$filaLote}", $fPrimera / $fIngresado);
                        $hojaIngreso->setCellValue("AI{$filaLote}", $fSegunda / $fIngresado);
                        $hojaIngreso->setCellValue("AJ{$filaLote}", $fTercera / $fIngresado);
                        $hojaIngreso->setCellValue("AK{$filaLote}", $fPiedra / $fIngresado);
                        $hojaIngreso->setCellValue("AL{$filaLote}", $fBasura / $fIngresado);
                    }
                    $hojaIngreso->setCellValue("AM{$filaLote}", $fTotal);
                    $hojaIngreso->setCellValue("AN{$filaLote}", abs($fIngresado - $fTotal) < 0.5 ? 'OK' : '');

                    if ($fechaFiltrado) {
                        $hojaIngreso->setCellValueExplicit(
                            "AR{$filaLote}",
                            ExcelDate::PHPToExcel($fechaFiltrado),
                            \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC
                        );
                        $hojaIngreso->getStyle("AR{$filaLote}")->getNumberFormat()->setFormatCode('dd/mm/yyyy');
                    }
                }

                $hojaIngreso->setCellValue("AP{$filaLote}", $diferenciaFinal);
                $hojaIngreso->setCellValue("AQ{$filaLote}", $porcentajeDiferenciaFinal);

                $this->aplicarEstilosFila($hojaIngreso, $filaLote, negrita: true);

                $filaIngreso++; // avanzar tras la fila totalizada
                

                // ================= HOJA FILTRADO (1 fila por registro real) =================
                foreach ($ingreso->filtrados as $f) {
                    $total = $f->primera + $f->segunda + $f->tercera + $f->piedra + $f->basura;

                    $hojaFiltrado->setCellValue("A{$filaFiltrado}", $ingreso->lote);
                    $hojaFiltrado->setCellValueExplicit(
                        "B{$filaFiltrado}",
                        ExcelDate::PHPToExcel($ingreso->fecha),
                        \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC
                    );
                    $hojaFiltrado->getStyle("B{$filaFiltrado}")->getNumberFormat()->setFormatCode('dd/mm/yyyy');
                    $hojaFiltrado->setCellValueExplicit(
                        "C{$filaFiltrado}",
                        ExcelDate::PHPToExcel($f->fecha_proceso),
                        \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC
                    );
                    $hojaFiltrado->getStyle("C{$filaFiltrado}")->getNumberFormat()->setFormatCode('dd/mm/yyyy');
                    $hojaFiltrado->setCellValue("D{$filaFiltrado}", $ingreso->campo);
                    $hojaFiltrado->setCellValue("E{$filaFiltrado}", $ingreso->total_kilos);
                    $hojaFiltrado->setCellValue("F{$filaFiltrado}", $f->kilos_ingresados);
                    $hojaFiltrado->setCellValue("G{$filaFiltrado}", $f->primera);
                    $hojaFiltrado->setCellValue("H{$filaFiltrado}", $f->segunda);
                    $hojaFiltrado->setCellValue("I{$filaFiltrado}", $f->tercera);
                    $hojaFiltrado->setCellValue("J{$filaFiltrado}", $f->piedra);
                    $hojaFiltrado->setCellValue("K{$filaFiltrado}", $f->basura);
                    $hojaFiltrado->setCellValue("L{$filaFiltrado}", $total);
                    $hojaFiltrado->setCellValue("M{$filaFiltrado}", abs($f->kilos_ingresados - $total) < 0.5 ? 'OK' : '');
                    if ($f->kilos_ingresados > 0) {
                        $hojaFiltrado->setCellValue("N{$filaFiltrado}", $f->primera / $f->kilos_ingresados);
                        $hojaFiltrado->setCellValue("O{$filaFiltrado}", $f->segunda / $f->kilos_ingresados);
                        $hojaFiltrado->setCellValue("P{$filaFiltrado}", $f->tercera / $f->kilos_ingresados);
                        $hojaFiltrado->setCellValue("Q{$filaFiltrado}", $f->piedra / $f->kilos_ingresados);
                        $hojaFiltrado->setCellValue("R{$filaFiltrado}", $f->basura / $f->kilos_ingresados);
                    }
                    $hojaFiltrado->setCellValue("S{$filaFiltrado}", abs($f->kilos_ingresados - $total) < 0.5 ? 'OK' : '');
                    $hojaFiltrado->setCellValue("T{$filaFiltrado}", $f->kilos_ingresados - $total);

                    $filaFiltrado++;
                }
            }

            // Última fila realmente escrita en 'ingreso' (para el rango del VLOOKUP de Venteado/Filtrado)
            $ultimaFilaIngreso = $filaIngreso - 1;

            // ================= HOJA VENTEADO (query independiente, puede haber venteados sin ingreso) =================
            $venteados = $this->construirQueryVenteadoFiltrado()
                ->orderBy('lote')
                ->orderBy('fecha_proceso')
                ->get();

            $loteActual = null;
            $filaInicioGrupo = null;

            foreach ($venteados as $v) {

                // Si cambiamos de lote, cerramos el grupo anterior con su fila de totales
                if ($loteActual !== null && $v->lote != $loteActual) {
                    $this->escribirTotalVenteado($hojaVenteado, $loteActual, $filaInicioGrupo, $filaVenteado - 1, $filaVenteado, $ultimaFilaIngreso);
                    $filaVenteado++;
                    $filaInicioGrupo = null;
                }

                if ($filaInicioGrupo === null) {
                    $filaInicioGrupo = $filaVenteado;
                }
                $loteActual = $v->lote;

                // ---- Fila de detalle (un proceso de venteado real) ----
                $hojaVenteado->setCellValue("A{$filaVenteado}", $v->lote);

                // B: FECHA -> VLOOKUP a 'ingreso' col 3 (C = Fecha). Aplica a TODAS las filas.
                $hojaVenteado->setCellValue(
                    "B{$filaVenteado}",
                    "=IFERROR(VLOOKUP(A{$filaVenteado},'ingreso'!\$A\$8:\$R\${$ultimaFilaIngreso},3,FALSE),\"\")"
                );
                $hojaVenteado->getStyle("B{$filaVenteado}")->getNumberFormat()->setFormatCode('dd/mm/yyyy');

                $hojaVenteado->setCellValueExplicit(
                    "C{$filaVenteado}",
                    ExcelDate::PHPToExcel($v->fecha_proceso),
                    \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC
                );
                $hojaVenteado->getStyle("C{$filaVenteado}")->getNumberFormat()->setFormatCode('dd/mm/yyyy');

                // D (Campo) y E (Kilos totales) NO van en la fila de detalle, solo en la fila totalizada.

                $hojaVenteado->setCellValue("F{$filaVenteado}", $v->kilos_ingresado);
                $hojaVenteado->setCellValue("G{$filaVenteado}", $v->limpia);
                $hojaVenteado->setCellValue("H{$filaVenteado}", "=F{$filaVenteado}-G{$filaVenteado}-I{$filaVenteado}");
                $hojaVenteado->setCellValue("I{$filaVenteado}", $v->polvillo);

                //$this->escribirFormulasComunesVenteado($hojaVenteado, $filaVenteado);
                $this->aplicarEstilosVenteado($hojaVenteado, $filaVenteado, negrita: false);

                $filaVenteado++;
            }

            // Cerrar el último grupo pendiente
            if ($loteActual !== null) {
                $this->escribirTotalVenteado($hojaVenteado, $loteActual, $filaInicioGrupo, $filaVenteado - 1, $filaVenteado, $ultimaFilaIngreso);
                $filaVenteado++;
            }




            return ExcelHelper::descargar($spreadsheet, 'REPORTE_COCHINILLA_INGRESOS.xlsx');

        } catch (\Throwable $th) {
            $this->alert('error', $th->getMessage());
        }
    }
    /**
     * Fila de totales al cierre de cada grupo de lote en la hoja Venteado.
     * D (Campo) y E (Kilos totales) SOLO existen en esta fila (vía VLOOKUP a 'ingreso').
     * F, G, I se suman del rango de filas de detalle del grupo; H se recalcula igual que siempre.
     */
    private function escribirTotalVenteado($hoja, $lote, int $filaInicio, int $filaFin, int $filaTotal, int $ultimaFilaIngreso): void
    {
        $hoja->setCellValue("A{$filaTotal}", $lote);

        // B: FECHA -> col 3 de 'ingreso' (C = Fecha)
        $hoja->setCellValue(
            "B{$filaTotal}",
            "=IFERROR(VLOOKUP(A{$filaTotal},'ingreso'!\$A\$8:\$R\${$ultimaFilaIngreso},3,FALSE),\"\")"
        );
        $hoja->getStyle("B{$filaTotal}")->getNumberFormat()->setFormatCode('dd/mm/yyyy');
        // D: CAMPO -> col 4 de 'ingreso' (D = Campo)

        $hoja->setCellValueExplicit(
            "D{$filaTotal}","=IFERROR(VLOOKUP(A{$filaTotal},'ingreso'!\$A\$8:\$R\${$ultimaFilaIngreso},4,FALSE),\"-\")",
            \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_FORMULA
        );
        // E: KILOS TOTALES -> col 11 de 'ingreso' (K = Total Kilos)
        $hoja->setCellValue(
            "E{$filaTotal}",
            "=IFERROR(VLOOKUP(A{$filaTotal},'ingreso'!\$A\$8:\$R\${$ultimaFilaIngreso},11,FALSE),\"-\")"
        );

        $hoja->setCellValue("F{$filaTotal}", "=SUM(F{$filaInicio}:F{$filaFin})");
        $hoja->setCellValue("G{$filaTotal}", "=SUM(G{$filaInicio}:G{$filaFin})");
        $hoja->setCellValue("I{$filaTotal}", "=SUM(I{$filaInicio}:I{$filaFin})");
        $hoja->setCellValue("H{$filaTotal}", "=F{$filaTotal}-G{$filaTotal}-I{$filaTotal}");

        $this->escribirFormulasComunesVenteado($hoja, $filaTotal);
        $this->aplicarEstilosVenteado($hoja, $filaTotal, negrita: true);
    }
    /**
     * Fórmulas J-P que se repiten igual en fila de detalle y en fila de totales de Venteado.
     * Se asume que F,G,H,I ya están escritos (valores o fórmulas) en $fila antes de llamar esto.
     */
    private function escribirFormulasComunesVenteado($hoja, int $fila): void
    {
        $hoja->setCellValue("J{$fila}", "=SUM(G{$fila}:I{$fila})");
        $hoja->setCellValue("K{$fila}", "=IF(SUM(G{$fila}:I{$fila})=F{$fila}, \"OK\", \"ERROR\")");
        $hoja->setCellValue("L{$fila}", "=IFERROR(G{$fila}/F{$fila}, 0)");
        $hoja->setCellValue("M{$fila}", "=IFERROR(H{$fila}/F{$fila}, 0)");
        $hoja->setCellValue("N{$fila}", "=IFERROR(I{$fila}/F{$fila}, 0)");
        $hoja->getStyle("L{$fila}:N{$fila}")
            ->getNumberFormat()
            ->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_PERCENTAGE_00);
        $hoja->setCellValue("O{$fila}", "=IF((L{$fila}+M{$fila}+N{$fila})=1, \"OK\", \"ERROR\")");
        $hoja->setCellValue("P{$fila}", "=E{$fila}-F{$fila}");
    }
    private function aplicarEstilosVenteado($hoja, int $fila, bool $negrita): void
    {
        if ($negrita) {
            $hoja->getStyle("A{$fila}:P{$fila}")->getFont()->setBold(true);
        }

        foreach (['A', 'C', 'D', 'E'] as $col) {
            $hoja->getStyle("{$col}{$fila}")->getFont()->getColor()->setARGB('FFFF0000'); // rojo
        }

        foreach (['L', 'M', 'N'] as $col) {
            $hoja->getStyle("{$col}{$fila}")->getFont()->getColor()->setARGB('FF0000FF'); // azul
        }
    }
    private function aplicarEstilosFila($hoja, int $fila, bool $negrita = false): void
    {
        if ($negrita) {
            $hoja->getStyle("A{$fila}:AR{$fila}")->getFont()->setBold(true);
        }

        $hoja->getStyle("A{$fila}:D{$fila}")->getFont()->getColor()->setARGB('FF0000FF'); // azul

        foreach (['E', 'F', 'J'] as $col) {
            $hoja->getStyle("{$col}{$fila}")->getFont()->getColor()->setARGB('FFFF0000'); // rojo
        }

        foreach (['Y', 'Z', 'AP', 'AQ'] as $col) {
            $hoja->getStyle("{$col}{$fila}")->getFont()->getColor()->setARGB('FF800080'); // morado
        }
    }
    private function construirQueryVenteadoFiltrado()
    {
        $query = CochinillaVenteado::query();

        if ($this->lote) {
            $query->where('lote', $this->lote);
        }

        if ($this->anioSeleccionado) {
            $query->whereYear('fecha_proceso', $this->anioSeleccionado);
        }

        if ($this->campoSeleccionado) {
            $campo = $this->campoSeleccionado;
            $query->whereHas('ingreso', function ($q) use ($campo) {
                $q->where('campo', $campo);
            });
        }

        if ($this->campaniaSeleccionado) {
            $nombreCampania = $this->campaniaSeleccionado;
            $query->whereHas('ingreso', function ($q) use ($nombreCampania) {
                $q->whereHas('campoCampania', function ($q2) use ($nombreCampania) {
                    $q2->where('nombre_campania', $nombreCampania);
                });
            });
        }

        if ($this->observacionSeleccionado) {
            $observacion = $this->observacionSeleccionado;
            $query->whereHas('ingreso', function ($q) use ($observacion) {
                $q->where('observacion', $observacion);
            });
        }

        return $query;
    }
    public function construirQueryFiltrada()
    {
        $query = CochinillaIngreso::query();

        if ($this->lote) {
            $query->where('lote', $this->lote);
        }
        if ($this->filtroVenteado) {
            if ($this->filtroVenteado == 'conventeado') {
                $query->whereHas('venteados');
            }
            if ($this->filtroVenteado == 'sinventeado') {
                $query->whereDoesntHave('venteados');
            }
        }
        if ($this->filtroFiltrado) {
            if ($this->filtroFiltrado === 'confiltrado') {
                $query->whereHas('filtrados');
            }
            if ($this->filtroFiltrado === 'sinfiltrado') {
                $query->whereDoesntHave('filtrados');
            }
        }
        if ($this->campoSeleccionado) {
            $query->where('campo', $this->campoSeleccionado);
        }
        if ($this->campaniaSeleccionado) {
            $nombreCampania = $this->campaniaSeleccionado;
            $query->whereHas('campoCampania', function ($q) use ($nombreCampania) {
                $q->where('nombre_campania', $nombreCampania);
            });
        }
        if ($this->observacionSeleccionado) {
            $query->where('observacion', $this->observacionSeleccionado);
        }

        return $query;
    }
    public function render()
    {
        $query = $this->construirQueryFiltrada()
            ->with(['detalles', 'campoCampania', 'detalles.observacionRelacionada', 'venteados', 'filtrados']);

        // Clonamos antes de aplicar el año, para no perder años disponibles
        $this->aniosDisponibles = (clone $query)
            ->select(DB::raw('YEAR(fecha) as anio'))
            ->groupBy(DB::raw('YEAR(fecha)'))
            ->pluck('anio')
            ->toArray();

        if ($this->anioSeleccionado) {
            $query->whereYear('fecha', $this->anioSeleccionado);
        }

        $cochinillaIngresos = $query->orderBy('lote', 'desc')->paginate(10);

        return view('livewire.cochinilla-ingreso-component', [
            'cochinillaIngresos' => $cochinillaIngresos
        ]);
    }
}

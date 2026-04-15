<?php

namespace App\Livewire\GestionAlmacen;

use App\Models\AlmacenProductoSalida;
use App\Models\DistribucionCombustible;
use App\Models\Maquinaria;
use App\Models\Producto;
use App\Support\ExcelHelper;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;

class DistribucionCombustibleReporteComponent extends Component
{
    use LivewireAlert;

    public bool $modalDistribucionReporte = false;
    public ?int $anioSeleccionado = null;
    public ?int $mesSeleccionado = null;
    public ?int $productoSeleccionado = null;
    public ?int $maquinariaSeleccionada = null;
    public array $productos = [];
    public array $maquinarias = [];
    public array $filas = [];
    protected $listeners = ['descargarReporteDistribuciones'];
    public function mount()
    {
        $this->anioSeleccionado = date('Y');
        $this->mesSeleccionado = date('m');
        $this->productos = Producto::deTipo('combustible')->pluck('nombre_comercial', 'id')->toArray();
        $this->maquinarias = Maquinaria::orderBy('nombre')->pluck('nombre', 'id')->toArray();

    }
    public function descargarReporteDistribuciones(): void
    {
        $this->modalDistribucionReporte = true;
        $this->generarData();
    }
    public function updated($propertyName)
    {
        if (in_array($propertyName, ['anioSeleccionado', 'mesSeleccionado', 'productoSeleccionado', 'maquinariaSeleccionada'])) {
            $this->generarData();
        }
    }

    public function generarData(): void
    {
        $this->filas = [];

        if (!$this->anioSeleccionado)
            return;

        $query = AlmacenProductoSalida::with(['maquinaria', 'producto'])
            ->whereYear('fecha_reporte', $this->anioSeleccionado)
            ->whereHas('producto', fn($q) => $q->whereHas('categoria', fn($cat) => $cat->where('codigo', 'combustible')))
            ->where(fn($q) => $q->whereNull('campo_nombre')->orWhere('campo_nombre', ''));

        if ($this->mesSeleccionado)
            $query->whereMonth('fecha_reporte', $this->mesSeleccionado);
        if ($this->productoSeleccionado)
            $query->where('producto_id', $this->productoSeleccionado);
        if ($this->maquinariaSeleccionada)
            $query->where('maquinaria_id', $this->maquinariaSeleccionada);

        $salidas = $query->orderBy('fecha_reporte')->get();

        $todasDistribuciones = DistribucionCombustible::with('maquinaria')
            ->whereIn('almacen_producto_salida_id', $salidas->pluck('id'))
            ->get()
            ->groupBy('almacen_producto_salida_id');

        foreach ($salidas as $salida) {
            $distribuciones = $todasDistribuciones[$salida->id] ?? collect();
            $totalHorasSalida = $distribuciones->sum('horas');

            // Fila Cabecera (Salida de Almacén)
            $this->filas[] = [
                'es_salida' => true,
                'fecha' => $salida->fecha_reporte,
                'maquinaria' => $salida->maquinaria?->nombre ?? '—',
                'actividad' => 'SALIDA ALMACÉN',
                'campo' => '—',
                'cantidad' => $salida->cantidad,
                'costo_unitario' => $salida->costo_por_kg,
                'total_costo' => $salida->total_costo,
                'horas_total' => $totalHorasSalida,
                'ratio' => null, // No aplica a la cabecera
                'hora_inicio' => null,
                'hora_fin' => null,
            ];

            foreach ($distribuciones as $dist) {
                $ratio = $totalHorasSalida > 0 ? ($dist->horas / $totalHorasSalida) : 0;
                $cantCombustible = $salida->cantidad * $ratio;
                $costoDist = $cantCombustible * ($salida->costo_por_kg ?? 0);

                $this->filas[] = [
                    'es_salida' => false,
                    'fecha' => $dist->fecha,
                    'maquinaria' => $dist->maquinaria?->nombre ?? '—',
                    'actividad' => $dist->actividad,
                    'campo' => $dist->campo,
                    'cantidad' => $cantCombustible,
                    'costo_unitario' => $salida->costo_por_kg,
                    'total_costo' => $costoDist,
                    'horas_total' => $dist->horas,
                    'ratio' => $ratio,
                    'hora_inicio' => $dist->hora_inicio, // Asegúrate que estos campos existan en el modelo
                    'hora_fin' => $dist->hora_salida,
                ];
            }
        }
    }
    public function exportarExcel()
    {
        try {
            if (empty($this->filas)) {
                $this->alert('warning', 'No hay datos para exportar.');
                return;
            }

            $spreadsheet = ExcelHelper::cargarPlantilla('reporte_almacen_combustible_distribucion.xlsx');
            $hoja = $spreadsheet->getSheetByName('DISTRIBUCION') ?? $spreadsheet->getActiveSheet();

            // Filtros
            $hoja->setCellValue("C3", $this->anioSeleccionado ?? '');
            $hoja->setCellValue("C4", $this->mesSeleccionado ? mb_strtoupper(Carbon::create()->month($this->mesSeleccionado)->monthName) : 'TODOS');
            $hoja->setCellValue("C5", $this->productoSeleccionado ? ($this->productos[$this->productoSeleccionado] ?? '') : 'TODOS');
            $hoja->setCellValue("C6", $this->maquinariaSeleccionada ? ($this->maquinarias[$this->maquinariaSeleccionada] ?? '') : 'TODOS');

            $table = $hoja->getTableByName('DistribucionTable');
            $filaActual = $table ? ExcelHelper::primeraFila($table) + 1 : 8;

            foreach ($this->filas as $datos) {
                $excelFecha = \PhpOffice\PhpSpreadsheet\Shared\Date::PHPToExcel(Carbon::parse($datos['fecha']));

                if ($datos['es_salida']) {
                    $hoja->setCellValue("A{$filaActual}", $excelFecha);
                    $hoja->setCellValue("H{$filaActual}", $datos['cantidad']);
                    $hoja->setCellValue("J{$filaActual}", $datos['maquinaria']);
                    $hoja->setCellValue("K{$filaActual}", $datos['costo_unitario']);
                    $hoja->setCellValue("M{$filaActual}", $datos['total_costo']);
                    $this->aplicarEstiloFila($hoja, $filaActual, true);
                } else {
                    $hoja->setCellValue("A{$filaActual}", $excelFecha);
                    $hoja->setCellValue("B{$filaActual}", $datos['hora_inicio']);
                    $hoja->setCellValue("C{$filaActual}", $datos['hora_fin']);
                    $hoja->setCellValue("D{$filaActual}", $datos['horas_total']); // Tiempo labor
                    $hoja->setCellValue("E{$filaActual}", $datos['campo']);
                    $hoja->setCellValue("F{$filaActual}", $datos['cantidad']); // Cant. Combustible
                    $hoja->setCellValue("G{$filaActual}", $datos['total_costo']); // Costo Combustible
                    $hoja->setCellValue("I{$filaActual}", $datos['actividad']);
                    $hoja->setCellValue("J{$filaActual}", $datos['maquinaria']);
                    $hoja->setCellValue("K{$filaActual}", $datos['costo_unitario']); // Precio
                    $hoja->setCellValue("L{$filaActual}", $datos['ratio']);
                    $hoja->setCellValue("M{$filaActual}", ($datos['horas_total'] > 0) ? ($datos['total_costo'] / $datos['horas_total']) : 0); // Valor/Costo hora

                    $this->aplicarEstiloFila($hoja, $filaActual, false);
                }

                // Formatos
                $hoja->getStyle("A{$filaActual}")->getNumberFormat()->setFormatCode('DD-MMM');
                if (!$datos['es_salida']) {
                    $hoja->getStyle("L{$filaActual}")->getNumberFormat()->setFormatCode('0.00%');
                    $hoja->getStyle("F{$filaActual}")->getNumberFormat()->setFormatCode('#,##0.00');
                }
                $filaActual++;
            }

            if ($table)
                ExcelHelper::actualizarRangoTabla($table, $filaActual - 1);

            $nombreArchivo = "Reporte_Distribucion_" . now()->format('Ymd_His') . ".xlsx";
            $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);

            return response()->streamDownload(fn() => $writer->save('php://output'), $nombreArchivo);

        } catch (\Exception $e) {
            $this->alert('error', 'Error: ' . $e->getMessage());
        }
    }

    /**
     * Aplica estilos rápidos para diferenciar visualmente en el Excel
     */
    private function aplicarEstiloFila($hoja, $fila, $esSalida)
    {
        if ($esSalida) {
            $hoja->getStyle("A{$fila}:M{$fila}")->applyFromArray([
                'font' => ['bold' => true, 'color' => ['rgb' => 'FF0000']],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'F0F7FF']
                ]
            ]);
        } else {
            $hoja->getStyle("A{$fila}")->getFill()
                ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()->setRGB('FFFF00'); // Amarillo para fecha en distribucion
        }
    }
    public function render()
    {
        return view('livewire.gestion-almacen.distribucion-combustible-reporte-component');
    }
}
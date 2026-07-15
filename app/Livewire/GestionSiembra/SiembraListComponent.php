<?php

namespace App\Livewire\GestionSiembra;

use App\Models\Siembra;
use App\Services\SiembraServicio;
use App\Services\AuditoriaServicio;
use App\Support\ExcelHelper;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;
use Livewire\WithPagination;
use Exception;

class SiembraListComponent extends Component
{
    use WithPagination;
    use LivewireAlert;

    // Filtros de la tabla principal
    public $filtroCampo = '';
    public $filtroAnio = '';
    public $aniosDisponibles = [];

    // Filtros independientes del resumen anual
    public $filtroResumenCampo = '';
    public $filtroResumenAnio = '';

    public $sortField = 'fecha_siembra';
    public $sortDirection = 'asc';
    public $perPage = 10;
    public $mostrarAuditoria = false;

    protected $listeners = ['siembraGuardada' => '$refresh', 'eliminarSiembra'];

    public function mount()
    {
        $this->aniosDisponibles = SiembraServicio::aniosDisponibles();
    }

    public function sortBy($field)
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
    }

    public function updatingFiltroCampo()
    {
        $this->resetPage();
    }

    public function updatingFiltroAnio()
    {
        $this->resetPage();
    }

    public function preguntarEliminarSiembra($id)
    {
        $this->confirm('¿Está seguro(a) que desea eliminar el registro?', [
            'onConfirmed' => 'eliminarSiembra',
            'data' => ['siembraId' => $id],
        ]);
    }

    public function mostrarAuditoriaForm()
    {
        $this->resetPage();
        $this->mostrarAuditoria = true;
    }

    public function eliminarSiembra($data)
    {
        try {
            SiembraServicio::eliminar($data['siembraId']);
            $this->alert('success', 'La siembra se eliminó correctamente.');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            $this->dispatch('log', 'Registro no encontrado: ' . $e->getMessage());
            $this->alert('error', 'La siembra no existe o ya fue eliminado.');
        } catch (\Exception $e) {
            $this->dispatch('log', 'Error al eliminar: ' . $e->getMessage());
            $this->alert('error', $e->getMessage());
        }
    }

    /**
     * Exporta un reporte con solo Fecha de Siembra y Campo,
     * respetando los filtros de la tabla principal (filtroCampo / filtroAnio).
     *
     * NOTA: ajusta el nombre de la plantilla, la hoja y la tabla
     * a como los tengas creados en tu archivo .xlsx.
     */
    public function exportarReporte()
    {
        try {
            $spreadsheet = ExcelHelper::cargarPlantilla('rpt_tmpl_reporte_siembras.xlsx');
            $hoja = $spreadsheet->getSheetByName('SIEMBRAS');

            if (!$hoja) {
                throw new Exception("La plantilla no contiene la hoja 'SIEMBRAS'.");
            }

            $table = $hoja->getTableByName('tblSiembras');
            if (!$table) {
                throw new Exception("La plantilla no tiene una tabla llamada 'tblSiembras'.");
            }

            // Escribir filtros aplicados en el encabezado (ajusta celdas según tu plantilla)
            $hoja->setCellValue('B2', $this->filtroCampo ?: 'Todos');
            $hoja->setCellValue('B3', $this->filtroAnio ?: 'Todos');

            // Datos respetando filtros de la tabla principal
            $datos = SiembraServicio::query([
                'campo_nombre' => $this->filtroCampo,
                'anio' => $this->filtroAnio,
            ])
                ->orderBy($this->sortField, $this->sortDirection)
                ->get(['fecha_siembra', 'campo_nombre']);

            // Determinar fila de inicio de datos
            $rangoOriginal = $table->getRange();
            [$celdaInicio] = explode(':', $rangoOriginal);
            $colInicio = preg_replace('/[0-9]/', '', $celdaInicio);
            $filaEncabezado = (int) filter_var($celdaInicio, FILTER_SANITIZE_NUMBER_INT);
            $filaInicio = $filaEncabezado + 1;
            $filaActual = $filaInicio;

            foreach ($datos as $siembra) {
                // A: FECHA DE SIEMBRA
                $hoja->setCellValue("A{$filaActual}", ExcelDate::PHPToExcel($siembra->fecha_siembra));
                $hoja->getStyle("A{$filaActual}")
                    ->getNumberFormat()
                    ->setFormatCode('dd/mm/yyyy');

                // B: CAMPO
                $hoja->setCellValue("B{$filaActual}", $siembra->campo_nombre);

                $filaActual++;
            }

            $filaFin = max($filaActual - 1, $filaEncabezado + 1);
            $table->setRange("{$colInicio}{$filaEncabezado}:B{$filaFin}");

            return ExcelHelper::descargar($spreadsheet, 'REPORTE_SIEMBRAS.xlsx');

        } catch (\Throwable $th) {
            $this->alert('error', $th->getMessage());
        }
    }

    public function render()
    {
        $listaSiembra = SiembraServicio::listar(
            ['campo_nombre' => $this->filtroCampo, 'anio' => $this->filtroAnio],
            $this->sortField,
            $this->sortDirection,
            $this->perPage
        );

        $resumenAnual = SiembraServicio::resumenAnual([
            'campo_nombre' => $this->filtroResumenCampo,
            'anio' => $this->filtroResumenAnio,
        ]);

        $auditoriaHistorial = AuditoriaServicio::obtenerHistorial(Siembra::class);

        return view('livewire.gestion-siembra.siembra-list-component', [
            'siembraLista' => $listaSiembra,
            'resumenAnual' => $resumenAnual,
            'auditoriaHistorial' => $auditoriaHistorial,
        ]);
    }
}
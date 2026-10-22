<?php

namespace App\Livewire\Cochinilla;

use App\Services\Cochinilla\Excel\CochinillaExcelExportarConsulta;
use App\Services\Cochinilla\Excel\CochinillaExcelImportacionConsulta;
use App\Services\Cochinilla\Excel\CochinillaExcelImportacionProceso;
use App\Services\Cochinilla\Excel\CochinillaExcelLibro;
use App\Services\Cochinilla\Excel\CochinillaExcelReglas;
use App\Traits\HandlesAlerts;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;
use Livewire\WithFileUploads;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * Barra "Excel oficial de cochinilla" de los módulos Ingreso, Venteado, Filtrado e Infestación: exportar el archivo de
 * un año (desde el sistema + hojas no importadas del último archivo subido) e importarlo con vista previa por hoja.
 * El mismo archivo sirve para los cuatro módulos; $modulo solo elige la pestaña que se ve primero.
 */
class CochinillaExcelComponent extends Component
{
    use WithFileUploads, LivewireAlert, HandlesAlerts;

    public string $modulo = 'ingreso';
    public int $anio;

    public bool $mostrarImportar = false;
    public $archivo = null;
    public ?string $ruta = null;
    public ?string $nombreOriginal = null;
    public ?array $analisis = null;
    public string $pestania = 'ingreso';

    public function mount(string $modulo = 'ingreso'): void
    {
        $this->modulo = $modulo;
        $this->pestania = $modulo;
        $this->anio = now()->year;
    }

    public function exportar()
    {
        try {
            $libro = app(CochinillaExcelExportarConsulta::class)->libro($this->anio);
            return response()->streamDownload(fn() => (new Xlsx($libro))->save('php://output'), CochinillaExcelReglas::nombreArchivo($this->anio));
        } catch (\Throwable $e) {
            $this->errorAlert($e);
            return null;
        }
    }

    public function abrirImportar(): void
    {
        $this->reset(['archivo', 'ruta', 'analisis', 'nombreOriginal']);
        $this->resetErrorBag();
        $this->pestania = $this->modulo;
        $this->mostrarImportar = true;
    }

    public function updatedArchivo(): void
    {
        $this->analizar();
    }

    public function updatedAnio(): void
    {
        if ($this->ruta) {
            $this->analizar();
        }
    }

    private function analizar(): void
    {
        try {
            if ($this->archivo) {
                $this->validate(['archivo' => 'required|file|mimes:xlsx|max:30720'], ['archivo.mimes' => 'Sube el Excel oficial de cochinilla (.xlsx).']);
                $this->nombreOriginal = $this->archivo->getClientOriginalName();
                // El año sale del nombre del archivo si lo trae ("COCHINILLA 2026.xlsx")
                if (preg_match('/(20\d{2})/', $this->nombreOriginal, $m)) {
                    $this->anio = (int) $m[1];
                }
                $this->ruta = $this->archivo->store('importaciones/cochinilla', 'local');
                $this->archivo = null;
            }
            $this->analisis = app(CochinillaExcelImportacionConsulta::class)
                ->analizar(app(CochinillaExcelLibro::class)->leer(storage_path('app/' . $this->ruta)), $this->anio);
        } catch (\Throwable $e) {
            $this->analisis = null;
            $this->errorAlert($e);
        }
    }

    public function importar(): void
    {
        try {
            abort_unless($this->ruta, 400);
            $r = app(CochinillaExcelImportacionProceso::class)->importar(storage_path('app/' . $this->ruta), $this->anio, $this->nombreOriginal);
            $this->mostrarImportar = false;
            $this->dispatch('cochinillaExcelImportado');
            $this->alert('success', "Excel {$this->anio} importado: {$r['lotes_nuevos']} lote(s) nuevo(s), {$r['lotes_actualizados']} actualizado(s), "
                . "{$r['venteado']} venteado(s), {$r['filtrado']} filtrado(s), {$r['infestacion']} infestación(es).", ['timer' => 9000]);
        } catch (\Throwable $e) {
            $this->errorAlert($e);
        }
    }

    public function render()
    {
        return view('livewire.cochinilla.cochinilla-excel-component', [
            'anios' => app(CochinillaExcelExportarConsulta::class)->anios(),
        ]);
    }
}

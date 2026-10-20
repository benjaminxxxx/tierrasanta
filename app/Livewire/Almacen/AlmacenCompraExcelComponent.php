<?php

namespace App\Livewire\Almacen;

use App\Services\Almacen\Compra\AlmacenCompraExcel;
use App\Services\Almacen\Compra\AlmacenCompraImportacionConsulta;
use App\Services\Almacen\Compra\AlmacenCompraImportacionProceso;
use App\Traits\HandlesAlerts;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithFileUploads;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * Excel de compras en /almacen/compras:
 * - Exportar: se elige el mes; el Excel trae sus compras (una fila por producto) y las hojas Productos (ordenados, como
 *   sugerencia), Proveedores (por RUC) y Listas. Es también la plantilla para registrar compras nuevas.
 * - Importar: se sube el Excel, se muestra la vista previa (nuevas / ya registradas / con errores, productos y
 *   proveedores nuevos) y solo al confirmar se registran las nuevas.
 */
class AlmacenCompraExcelComponent extends Component
{
    use WithFileUploads, LivewireAlert, HandlesAlerts;

    public bool $mostrarExportar = false;
    public int $mes;
    public int $anio;

    public bool $mostrarImportar = false;
    public $archivo = null;
    public ?string $ruta = null;
    public ?array $analisis = null;

    public function mount(): void
    {
        $this->mes = now()->month;
        $this->anio = now()->year;
    }

    #[On('abrirExportarCompras')]
    public function abrirExportar(): void
    {
        $this->mostrarExportar = true;
    }

    public function exportar()
    {
        try {
            $libro = app(AlmacenCompraExcel::class)->exportar($this->anio, $this->mes);
            $this->mostrarExportar = false;
            return response()->streamDownload(fn() => (new Xlsx($libro))->save('php://output'), sprintf('COMPRAS_%04d_%02d.xlsx', $this->anio, $this->mes));
        } catch (\Throwable $e) {
            $this->errorAlert($e);
            return null;
        }
    }

    #[On('abrirImportarCompras')]
    public function abrirImportar(): void
    {
        $this->reset(['archivo', 'ruta', 'analisis']);
        $this->resetErrorBag();
        $this->mostrarImportar = true;
    }

    /** Al subir el archivo: leerlo y mostrar la vista previa (no registra nada). */
    public function updatedArchivo(): void
    {
        try {
            $this->validate(['archivo' => 'required|file|mimes:xlsx,xls|max:20480'], [
                'archivo.mimes' => 'Sube el Excel de compras (.xlsx).',
            ]);
            $this->ruta = $this->archivo->store('importaciones/compras', 'local');
            $this->analisis = app(AlmacenCompraImportacionConsulta::class)
                ->analizar(app(AlmacenCompraExcel::class)->leer(storage_path('app/' . $this->ruta)));
        } catch (\Throwable $e) {
            $this->analisis = null;
            $this->errorAlert($e);
        }
    }

    public function importar(): void
    {
        try {
            abort_unless($this->ruta, 400);
            $r = app(AlmacenCompraImportacionProceso::class)->importar(storage_path('app/' . $this->ruta));
            $this->mostrarImportar = false;
            $this->dispatch('nuevaCompraRegistrada');
            $this->alert('success', "Se registraron {$r['compras']} compra(s) por S/ " . number_format($r['total'], 2)
                . ($r['productos'] ? ", {$r['productos']} producto(s) nuevo(s)" : '')
                . ($r['proveedores'] ? ", {$r['proveedores']} proveedor(es) nuevo(s)" : '')
                . ". Su kardex queda pendiente de regenerar (Tareas pendientes).", ['timer' => 8000]);
        } catch (\Throwable $e) {
            $this->errorAlert($e);
        }
    }

    public function render()
    {
        return view('livewire.almacen.almacen-compra-excel-component');
    }
}

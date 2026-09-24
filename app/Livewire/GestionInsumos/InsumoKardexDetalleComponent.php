<?php

namespace App\Livewire\GestionInsumos;

use App\Models\InsKardex;
use App\Services\Almacen\InsumoKardexImportarServicio;
use App\Services\Almacen\InsumoKardexMovimientosServicio;
use App\Traits\HandlesAlerts;
use Livewire\Component;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\WithFileUploads;

class InsumoKardexDetalleComponent extends Component
{
    use LivewireAlert, WithFileUploads, HandlesAlerts;

    public $insumoKardex;
    public $movimientos = [];
    public $archivoExcelKardex;
    public $kardexOpuesto = null;
    public $tipoOpuesto = null;
    public array $datosImportacionKardex = [];
    public bool $mostrarModalImportacionKardex = false;
    protected $listeners = ['kardexCerrado'=>'obtenerMovimientos'];
    public function mount($insumoKardexId)
    {
        $this->insumoKardex = InsKardex::with(['producto'])
            ->findOrFail($insumoKardexId);

        $this->tipoOpuesto = $this->insumoKardex->tipo === 'blanco'
            ? 'negro'
            : 'blanco';
        $this->kardexOpuesto = InsKardex::where('producto_id', $this->insumoKardex->producto_id)
            ->where('anio', $this->insumoKardex->anio)
            ->where('tipo', $this->tipoOpuesto)
            ->first();
        // Cargar movimientos
        $this->obtenerMovimientos();

    }
    public function obtenerMovimientos()
    {
        $this->insumoKardex->refresh();
        
        $this->movimientos = $this->insumoKardex
            ->movimientos()
            ->orderBy('fecha')
            ->orderBy('id')
            ->get()
            ->map(function ($movimiento) {
                $esEntrada = $movimiento->tipo_mov === 'entrada';
                $esSalida = $movimiento->tipo_mov === 'salida';

                // Entradas
                $movimiento->entrada_cantidad_fmt = $esEntrada ? number_format($movimiento->entrada_cantidad, 3) : '-';
                $movimiento->entrada_costo_unitario_fmt = $esEntrada ? number_format($movimiento->entrada_costo_unitario, 2) : '-';
                $movimiento->entrada_costo_total_fmt = $esEntrada ? number_format($movimiento->entrada_costo_total, 2) : '-';

                // Salidas
                $movimiento->salida_cantidad_fmt = $esSalida ? number_format($movimiento->salida_cantidad, 3) : '-';
                $movimiento->salida_destino_fmt = $esSalida ? ($movimiento->salida_lote ?? $movimiento->salida_maquinaria ?? '-') : '-';
                $movimiento->salida_costo_unitario_fmt = $esSalida ? number_format($movimiento->salida_costo_unitario, 2) : '-';
                $movimiento->salida_costo_total_fmt = $esSalida ? number_format($movimiento->salida_costo_total, 2) : '-';

                // Saldo Final
                $movimiento->saldo_cantidad_fmt = number_format($movimiento->saldo_cantidad, 3);
                $movimiento->saldo_costo_unitario_fmt = number_format($movimiento->saldo_costo_unitario, 2);
                $movimiento->saldo_costo_total_fmt = number_format($movimiento->saldo_costo_total, 2);

                return $movimiento;
            });
        //dd($this->movimientos->first());
        $this->dispatch('regenerarTablaKardex', movimientos: $this->movimientos->toArray());
    }
    public function updatedArchivoExcelKardex()
    {
        try {

            $this->datosImportacionKardex = app(InsumoKardexImportarServicio::class)->previsualizar(
                $this->archivoExcelKardex,
                $this->insumoKardex
            );
            $this->mostrarModalImportacionKardex = true;
        } catch (\Throwable $th) {
            $this->errorAlert($th);
        }
    }
    public function generarDetalleKardexInsumo()
    {
        try {

            app(InsumoKardexMovimientosServicio::class)->generarMovimientos($this->insumoKardex);
            $this->obtenerMovimientos();
            $this->alert('success', 'Compras y Salidas cargados desde el kardex correctamente.');
        } catch (\Throwable $th) {
            $this->errorAlert($th);
        }
    }
    public function confirmarImportacionKardex()
    {
        try {
            app(InsumoKardexImportarServicio::class)->confirmarImportacion(
                $this->datosImportacionKardex,
                $this->insumoKardex
            );

            app(InsumoKardexMovimientosServicio::class)->generarMovimientos($this->insumoKardex);
            $this->obtenerMovimientos();
            $this->cerrarModalImportacionKardex();
            $this->alert('success', 'Compras y Salidas cargados desde el kardex correctamente.');
        } catch (\Throwable $th) {
            $this->errorAlert($th);
        }
    }

    public function cerrarModalImportacionKardex()
    {
        $this->mostrarModalImportacionKardex = false;
        $this->datosImportacionKardex = [];
        $this->reset('archivoExcelKardex');
    }
    public function render()
    {
        return view('livewire.gestion-insumos.insumo-kardex-detalle-component');
    }
}

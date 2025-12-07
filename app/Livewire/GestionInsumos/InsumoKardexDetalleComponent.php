<?php

namespace App\Livewire\GestionInsumos;

use App\Models\InsKardex;
use App\Services\Almacen\InsumoKardexImportarServicio;
use App\Services\Almacen\InsumoKardexMovimientosServicio;
use Livewire\Component;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\WithFileUploads;

class InsumoKardexDetalleComponent extends Component
{
    use LivewireAlert, WithFileUploads;

    public $insumoKardex;
    public $movimientos = [];
    public $archivoExcelKardex;
    public function mount($insumoKardexId)
    {
        $this->insumoKardex = InsKardex::findOrFail($insumoKardexId);

        // Cargar movimientos
        $this->movimientos = $this->insumoKardex
            ->movimientos()
            ->orderBy('fecha')
            ->orderBy('id')
            ->get();
            
    }
    public function updatedArchivoExcelKardex()
    {
        try {
            app(InsumoKardexImportarServicio::class)->procesar(
                $this->archivoExcelKardex,
                $this->insumoKardex);
        } catch (\Throwable $th) {
            $this->alert('error', 'Error en Procesar Archivo: ' . $th->getMessage(), [

                    'position' => 'center',
                    'toast' => false,
                    'timer' => null,
                ]);
        }
    }
    public function generarDetalleKardexInsumo(){
        try {
            app(InsumoKardexMovimientosServicio::class)->generarMovimientos($this->insumoKardex);
        } catch (\Throwable $th) {
            $this->alert('error', 'Error en Procesar Archivo: ' . $th->getMessage(), [

                    'position' => 'center',
                    'toast' => false,
                    'timer' => null,
                ]);
        }
    }
    public function render()
    {
        return view('livewire.gestion-insumos.insumo-kardex-detalle-component');
    }
}

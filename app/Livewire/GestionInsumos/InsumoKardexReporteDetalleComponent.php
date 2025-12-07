<?php

namespace App\Livewire\GestionInsumos;

use App\Models\InsKardexReporte;
use App\Services\KardexServicio;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;

class InsumoKardexReporteDetalleComponent extends Component
{
    use LivewireAlert;
    public $insumoKardexReporteId;
    public $insumoKardexReporte = [];
    public function mount($insumoKardexReporteId)
    {
        $this->insumoKardexReporte = InsKardexReporte::find($insumoKardexReporteId);
    }
    public function procesarKardexConsolidado()
    {
        try {
            KardexServicio::procesarKardexConsolidado($this->kardexId, $this->verBlanco);
            $this->alert('success', 'Datos procesados correctamente.');
        } catch (\Throwable $e) {
            logger()->error("Error al procesar kardex consolidado: " . $e->getMessage());
            $this->alert('error', 'Ocurrió un error al procesar el kardex.'. $e->getMessage());
        }
    }
    public function render()
    {
        return view('livewire.gestion-insumos.insumo-kardex-reporte-detalle-component');
    }
}

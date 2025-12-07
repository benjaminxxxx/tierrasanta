<?php

namespace App\Livewire\GestionInsumos;

use App\Models\InsKardexReporte;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;
use Livewire\WithoutUrlPagination;
use Livewire\WithPagination;

class InsumoKardexReporteComponent extends Component
{
    use LivewireAlert, WithPagination, WithoutUrlPagination;
    public $filtroAnio;
    public $aniosDisponibles = [];
    protected $listeners = ['insumoKardexRefrescar'];
    public function mount(){
        $this->insumoKardexRefrescar();
    }
    public function insumoKardexRefrescar(){
        $this->resetPage();
        $this->aniosDisponibles = InsKardexReporte::selectRaw('anio')
            ->distinct()
            ->orderBy('anio', 'desc')
            ->pluck('anio')
            ->toArray();
    }
    public function eliminarInsumoKardexReporte($reporteId)
    {
        $reporte = InsKardexReporte::find($reporteId);
        if (!$reporte) {
            return $this->alert('error', 'El reporte de kardex no existe');
        }
        try {
            $reporte->delete();
            $this->alert('success', 'Reporte de kardex eliminado correctamente');
            $this->dispatch("insumoKardexRefrescar");
        } catch (\Exception $e) {
            $this->alert('error', 'Error al eliminar el reporte de kardex: ' . $e->getMessage());
        }

    }
    public function render()
    {
        $query = InsKardexReporte::query();
        if ($this->filtroAnio) {
            $query->where('anio', $this->filtroAnio);
        }
        $insumoKardexReportes = $query->orderBy('created_at', 'desc')->paginate(20);
        return view('livewire.gestion-insumos.insumo-kardex-reporte-component', [
            'insumoKardexReportes' => $insumoKardexReportes,
        ]);
    }
}

<?php

namespace App\Livewire\Almacen\Kardex;

use App\Models\InsKardexReporte;
use Illuminate\Support\Facades\Storage;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;
use Livewire\WithoutUrlPagination;
use Livewire\WithPagination;

class InsumoKardexReporteComponent extends Component
{
    use LivewireAlert, WithPagination, WithoutUrlPagination;
    public $filtroAnio;
    public $filtroTipo;
    public $filtroGrupo;
    public $aniosDisponibles = [];
    public $gruposDisponibles = [];
    protected $listeners = ['insumoKardexRefrescar'];

    public function mount()
    {
        $this->gruposDisponibles = InsKardexReporte::gruposDisponibles();
        $this->insumoKardexRefrescar();
    }

    public function insumoKardexRefrescar()
    {
        $this->resetPage();
        $this->aniosDisponibles = InsKardexReporte::selectRaw('anio')
            ->distinct()
            ->orderBy('anio', 'desc')
            ->pluck('anio')
            ->toArray();
    }

    public function updated($propiedad)
    {
        if (str_starts_with($propiedad, 'filtro')) {
            $this->resetPage();
        }
    }

    public function limpiarFiltros()
    {
        $this->reset(['filtroAnio', 'filtroTipo', 'filtroGrupo']);
        $this->resetPage();
    }

    public function eliminarInsumoKardexReporte($reporteId)
    {
        $reporte = InsKardexReporte::find($reporteId);
        if (!$reporte) {
            return $this->alert('error', 'El reporte de kardex no existe');
        }
        try {
            if ($reporte->file) {
                Storage::disk('public')->delete($reporte->file);
            }
            $reporte->delete();
            $this->alert('success', 'Reporte de kardex eliminado correctamente');
            $this->dispatch("insumoKardexRefrescar");
        } catch (\Exception $e) {
            $this->alert('error', 'Error al eliminar el reporte de kardex: ' . $e->getMessage());
        }
    }

    public function render()
    {
        $query = InsKardexReporte::withCount('detalles');
        if ($this->filtroAnio) {
            $query->where('anio', $this->filtroAnio);
        }
        if ($this->filtroTipo) {
            $query->where('tipo_kardex', $this->filtroTipo);
        }
        if ($this->filtroGrupo) {
            $query->whereJsonContains('grupos_operativos', $this->filtroGrupo);
        }
        $insumoKardexReportes = $query->orderBy('anio', 'desc')->orderBy('created_at', 'desc')->paginate(20);

        return view('livewire.almacen.kardex.insumo-kardex-reporte-component', [
            'insumoKardexReportes' => $insumoKardexReportes,
        ]);
    }
}

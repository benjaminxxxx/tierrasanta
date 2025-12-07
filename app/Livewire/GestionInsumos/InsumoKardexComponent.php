<?php

namespace App\Livewire\GestionInsumos;

use App\Models\InsKardex;
use App\Models\InsKardexReporte;
use App\Services\Almacen\InsumoKardexServicio;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;
use Livewire\WithoutUrlPagination;
use Livewire\WithPagination;

class InsumoKardexComponent extends Component
{
    use LivewireAlert, WithPagination, WithoutUrlPagination;
    public $filtroAnio;
    public $aniosDisponibles = [];
    // Propiedades de paginación
    public $perPage = 20;

    #[Url]
    public $filtroTipo = '';

    #[Url]
    public $filtroEstado = '';

    #[Url]
    public $filtroMetodo = '';
    protected $listeners = ['insumoKardexRefrescar'];
    public function mount()
    {
        $this->insumoKardexRefrescar();
    }
    public function insumoKardexRefrescar()
    {
        $this->resetPage();
        $this->aniosDisponibles = InsKardex::selectRaw('anio')
            ->distinct()
            ->orderBy('anio', 'desc')
            ->pluck('anio')
            ->toArray();
    }
    public function eliminarInsumoKardex($reporteId)
    {
        try {

            app(InsumoKardexServicio::class)->eliminarKardex($reporteId);
            $this->alert('success', 'Kardex eliminado correctamente');
        } catch (\Exception $e) {
            $this->alert('error', 'Error al eliminar el kardex: ' . $e->getMessage());
        }

    }
    public function render()
    {
        // 1. Definir el array de filtros
        $filters = [
            'filtroAnio' => $this->filtroAnio,
            'filtroTipo' => $this->filtroTipo,
            'filtroEstado' => $this->filtroEstado,
            'filtroMetodo' => $this->filtroMetodo,
        ];

        // 2. Llamar al servicio para obtener la lista filtrada y paginada
        $kardexes = app(InsumoKardexServicio::class)->obtenerKardexes($filters, $this->perPage);

        return view('livewire.gestion-insumos.insumo-kardex-component', [
            'kardexes' => $kardexes,
        ]);
    }
}

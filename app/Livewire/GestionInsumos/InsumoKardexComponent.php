<?php

namespace App\Livewire\GestionInsumos;

use App\Models\InsKardex;
use App\Models\InsKardexReporte;
use App\Services\Almacen\InsumoKardexServicio;
use Illuminate\Support\Carbon;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;
use Livewire\WithoutUrlPagination;
use Livewire\WithPagination;

class InsumoKardexComponent extends Component
{
    use LivewireAlert, WithPagination, WithoutUrlPagination;
    public $filtroAnio;
    public $filtroProducto = '';
    public $aniosDisponibles = [];
    // Propiedades de paginación
    public $perPage = 20;
    public $sortField = 'anio';
    public $sortDirection = 'desc';

    #[Url]
    public $filtroTipo;

    #[Url]
    public $filtroEstado;

    #[Url]
    public $filtroMetodo;
    protected $listeners = ['insumoKardexRefrescar'];
    const SESSION_KEY = 'kardex_filtros';
    public function mount()
    {
        $sessionFilters = session(self::SESSION_KEY, []);

        // Año (siempre default current year si no hay nada)
        $this->filtroAnio = $this->filtroAnio
            ?? $sessionFilters['filtroAnio']
            ?? Carbon::now()->year;

        $this->filtroTipo = $this->filtroTipo
            ?? $sessionFilters['filtroTipo']
            ?? '';

        $this->filtroEstado = $this->filtroEstado
            ?? $sessionFilters['filtroEstado']
            ?? '';

        $this->filtroMetodo = $this->filtroMetodo
            ?? $sessionFilters['filtroMetodo']
            ?? '';

        $this->sortField = $sessionFilters['sortField'] ?? 'anio';
        $this->sortDirection = $sessionFilters['sortDirection'] ?? 'desc';

        $this->insumoKardexRefrescar();
    }
    public function updated($property)
    {
        if (
            in_array($property, [
                'filtroAnio',
                'filtroTipo',
                'filtroEstado',
                'filtroMetodo'
            ])
        ) {
            session()->put(self::SESSION_KEY, [
                'filtroAnio' => $this->filtroAnio,
                'filtroTipo' => $this->filtroTipo,
                'filtroEstado' => $this->filtroEstado,
                'filtroMetodo' => $this->filtroMetodo,
            ]);

            // resetear paginación al cambiar filtros
            $this->resetPage();
        }
    }
    public function updatedSortField()
    {
        $this->guardarEstado();
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
    public function sortBy($field)
    {
        if ($this->sortField === $field) {
            // toggle asc/desc
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }

        session()->put(self::SESSION_KEY, [
            'filtroAnio' => $this->filtroAnio,
            'filtroTipo' => $this->filtroTipo,
            'filtroEstado' => $this->filtroEstado,
            'filtroMetodo' => $this->filtroMetodo,
            'sortField' => $this->sortField,
            'sortDirection' => $this->sortDirection,
        ]);
    }
    public function render()
    {
        // 1. Definir el array de filtros
        $filters = [
            'filtroProducto' => $this->filtroProducto,
            'filtroAnio' => $this->filtroAnio,
            'filtroTipo' => $this->filtroTipo,
            'filtroEstado' => $this->filtroEstado,
            'filtroMetodo' => $this->filtroMetodo,
        ];

        // 2. Llamar al servicio para obtener la lista filtrada y paginada
        $kardexes = app(InsumoKardexServicio::class)->obtenerKardexes(
            $filters,
            $this->perPage,
            $this->sortField,
            $this->sortDirection
        );
        return view('livewire.gestion-insumos.insumo-kardex-component', [
            'kardexes' => $kardexes,
        ]);
    }
}

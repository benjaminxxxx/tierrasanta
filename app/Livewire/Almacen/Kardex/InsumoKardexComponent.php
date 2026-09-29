<?php

namespace App\Livewire\Almacen\Kardex;

use App\Models\InsKardex;
use App\Models\InsKardexReporte;
use App\Models\StockProducto;
use App\Services\Almacen\Kardex\InsumoKardexServicio;
use App\Services\Almacen\Kardex\KardexActualizacionServicio;
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
    public $sortField = 'codigo_existencia';
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
        // Estado de la fecha de corte por kardex visible: null = al día, texto = motivo
        $actualizacion = app(KardexActualizacionServicio::class);
        $motivosDesactualizado = $kardexes->getCollection()
            ->mapWithKeys(fn($k) => [$k->id => $actualizacion->motivoDesactualizado($k)])
            ->all();

        // Stock actual real (stocks_productos) solo tiene sentido para el kardex del año vigente
        $anioVigente = (int) date('Y');
        $stockActual = StockProducto::whereIn('producto_id', $kardexes->getCollection()->where('anio', $anioVigente)->pluck('producto_id'))
            ->get()
            ->mapWithKeys(fn($s) => ["{$s->producto_id}|{$s->tipo_kardex}" => (float) $s->cantidad])
            ->all();

        return view('livewire.almacen.kardex.insumo-kardex-component', [
            'kardexes' => $kardexes,
            'motivosDesactualizado' => $motivosDesactualizado,
            'stockActual' => $stockActual,
            'anioVigente' => $anioVigente,
        ]);
    }
}

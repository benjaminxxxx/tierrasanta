<?php

namespace App\Livewire;

use App\Models\CategoriaPesticida;
use App\Models\InsCategoria;
use App\Models\InsUso;
use App\Models\Nutriente;
use App\Models\Producto;
use App\Services\Insumo\InsumoServicio;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;
use Livewire\WithPagination;

class ProductosComponent extends Component
{
    use LivewireAlert;
    use WithPagination;
    public $search = '';
    public $categoriaSeleccionada;
    public $sortField = 'nombre_comercial';
    public $sortDirection = 'asc';
    public $categorias = [];

    public bool $modalAuditoria = false;
    public array $auditoriaHistorial = [];
    public ?int $productoIdEliminar = null;

    // Propiedades nuevas
    public string $categoriaPesticida = '';
    public string $usoSeleccionado = '';
    public array $nutrientesSeleccionados = [];

    // En mount() cargar catálogos para los filtros
    public array $listaUsosFiltro = [];
    public array $listaNutrientesFiltro = [];
    public array $listaCategoriasPesticidaFiltro = [];

    protected $listeners = ['ActualizarProductos' => '$refresh', 'confirmarEliminarProducto'];
    public function mount()
    {
        $this->categorias = InsCategoria::orderBy('descripcion')->get();
        $this->listaUsosFiltro = InsUso::orderBy('nombre')
            ->get(['id', 'nombre'])
            ->toArray();

        $this->listaNutrientesFiltro = Nutriente::orderBy('nombre')
            ->get(['codigo', 'nombre'])
            ->toArray();

        $this->listaCategoriasPesticidaFiltro = CategoriaPesticida::orderBy('descripcion')
            ->get(['codigo', 'descripcion'])
            ->toArray();
    }
    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function confirmarEliminacion($id)
    {
        $this->confirm('¿Está seguro(a) que desea eliminar el producto?', [
            'onConfirmed' => 'confirmarEliminarProducto',
            'data' => ['id' => $id],
        ]);
    }
    public function confirmarEliminarProducto(array $data): void
    {
        try {
            InsumoServicio::eliminar($data['id']);
            $this->alert('success', 'Producto eliminado correctamente.');
            $this->dispatch('ActualizarProductos');
        } catch (\Throwable $e) {
            $this->alert('error', $e->getMessage());
        }
    }
    public function sortBy($field)
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
    }
    public function verAuditoriaProducto(int $id): void
    {
        $this->auditoriaHistorial = InsumoServicio::getAuditoria($id);
        $this->modalAuditoria = true;
    }
    public function limpiarFiltros(): void
    {
        $this->reset([
            'search',
            'categoriaSeleccionada',
            'categoriaPesticida',
            'usoSeleccionado',
            'nutrientesSeleccionados',
        ]);
    }
    public function render()
    {
        $productos = InsumoServicio::listarProductos(
            search: $this->search,
            categoriaCodigo: $this->categoriaSeleccionada,
            categoriaPesticida: $this->categoriaPesticida,
            usoId: $this->usoSeleccionado,
            nutrientes: $this->nutrientesSeleccionados,
            sortField: $this->sortField,
            sortDirection: $this->sortDirection,
        );

        return view('livewire.productos-component', compact('productos'));
    }
}

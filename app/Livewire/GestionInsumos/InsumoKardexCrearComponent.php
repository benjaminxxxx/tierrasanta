<?php

namespace App\Livewire\GestionInsumos;

use App\Models\InsKardex;
use App\Models\Producto;
use App\Services\ProductoServicio;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;

class InsumoKardexCrearComponent extends Component
{
    use LivewireAlert;

    public ?int $productoId = null;
    public ?Producto $producto = null;
    public array $kardexAgrupados = [];

    public ?int $kardexId = null;   // InsKardex->id seleccionado
    public ?string $tipoKardex = null;   // 'blanco' | 'negro'
    protected $listeners = ['ActualizarProductos', 'insumoKardexRefrescar'];
    public function ActualizarProductos(int $productoId): void
    {
        $this->productoId = $productoId;
        $this->cargarProducto($productoId);
    }
    public function insumoKardexRefrescar(int $kardexId): void
    {
        // Recarga el producto con sus kardex actualizados
        $this->cargarProducto($this->productoId);

        // Busca el kardex recién creado para saber su tipo
        $kardex = InsKardex::find($kardexId);

        if (!$kardex)
            return;

        // Autoselecciona
        $this->kardexId = $kardex->id;
        $this->tipoKardex = $kardex->tipo;
    }
    public function restaurarProducto(): void
    {
        if (!$this->producto?->trashed())
            return;

        $this->producto->restore();
        $this->producto = app(ProductoServicio::class)->encontrar($this->productoId);
        $this->alert('success', 'Producto restaurado correctamente');
    }

    public function updatedProductoId(?string $value): void
    {
        if (blank($value)) {
            $this->resetTodo();
            return;
        }

        $this->cargarProducto((int) $value);
    }

    private function cargarProducto(int $id): void
    {
        $servicio = app(ProductoServicio::class);
        $this->producto = $servicio->encontrar($id);

        if (!$this->producto) {
            $this->alert('error', 'Producto no encontrado');
            $this->resetTodo();
            return;
        }

        $this->kardexAgrupados = $servicio->kardexAgrupadosPorAnio($this->producto);
        $this->kardexId = null;
        $this->tipoKardex = null;
    }

    public function quitarProducto(): void
    {
        $this->resetTodo();
    }

    // ── Kardex ────────────────────────────────────────────────────
    public function seleccionarKardex(int $kardexId, string $tipo): void
    {
        // Toggle
        if ($this->kardexId === $kardexId && $this->tipoKardex === $tipo) {
            $this->kardexId = null;
            $this->tipoKardex = null;
            return;
        }

        $this->kardexId = $kardexId;
        $this->tipoKardex = $tipo;
    }

    // ── Helpers ───────────────────────────────────────────────────
    public function getProductos(string $search): array
    {
        return app(ProductoServicio::class)->buscar($search);
    }

    private function resetTodo(): void
    {
        $this->productoId = null;
        $this->producto = null;
        $this->kardexAgrupados = [];
        $this->kardexId = null;
        $this->tipoKardex = null;
    }

    public function render()
    {
        return view('livewire.gestion-insumos.insumo-kardex-crear-component');
    }
}
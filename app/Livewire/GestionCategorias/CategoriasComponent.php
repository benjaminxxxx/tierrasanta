<?php

namespace App\Livewire\GestionCategorias;

use App\Models\InsCategoria;
use App\Models\InsSubcategoria;
use App\Models\Producto;
use App\Services\ProductoServicio;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;

class CategoriasComponent extends Component
{
    use LivewireAlert;
    public bool $modalProductos = false;
    public ?int $subcategoriaActivaId = null;
    public string $subcategoriaNombre = '';
    public string $categoriaCodigo = '';

    // Lista A: tienen categoria pero sin subcategoria
    public array $productosCategoria = [];
    // Lista B: tienen categoria + subcategoria
    public array $productosSubcategoria = [];
    public $categorias = [];
    public $productoSeleccionado = null;
    public array $productosModal = [];
    public bool $modalProductosCategoria = false;
    public string $categoriaActivaNombre = '';
    public array $productosCategoriaSel = [];
    public function mount()
    {
        $this->categorias = InsCategoria::with(['subcategorias', 'insumos'])
            ->orderBy('descripcion')
            ->get();
    }
    public function verProductosCategoria(string $categoriaCodigo): void
    {
        $categoria = InsCategoria::find($categoriaCodigo);
        $this->categoriaActivaNombre = $categoria->descripcion;

        $this->productosCategoriaSel = Producto::with('subcategoria')
            ->where('categoria_codigo', $categoriaCodigo)
            ->orderBy('nombre_comercial')
            ->get(['id', 'nombre_comercial', 'categoria_codigo', 'subcategoria_id'])
            ->map(fn($p) => [
                'nombre' => $p->nombre_comercial,
                'categoria' => $p->categoria_codigo,
                'subcategoria' => $p->subcategoria?->nombre,
            ])->toArray();

        $this->modalProductosCategoria = true;
    }
    public function verListaProductos(int $subcategoriaId): void
    {
        $sub = InsSubcategoria::findOrFail($subcategoriaId);
        $this->subcategoriaActivaId = $subcategoriaId;
        $this->subcategoriaNombre = $sub->nombre;
        $this->categoriaCodigo = $sub->categoria_codigo;
        $this->productoSeleccionado = null;

        $this->productosModal = Producto::where('subcategoria_id', $subcategoriaId)
            ->get(['id', 'nombre_comercial', 'categoria_codigo', 'subcategoria_id'])
            ->map(fn($p) => [
                'id' => $p->id,
                'nombre' => $p->nombre_comercial,
                'categoria_codigo' => $p->categoria_codigo,
                'subcategoria_id' => $p->subcategoria_id,
                'es_nuevo' => false,
            ])->toArray();

        $this->modalProductos = true;
    }
    public function updatedProductoSeleccionado($value): void
    {
        if (blank($value))
            return;

        $producto = Producto::find($value);
        if (!$producto)
            return;

        $this->agregarProducto(
            $producto->id,
            $producto->nombre_comercial,
            $producto->categoria_codigo,
            $producto->subcategoria_id
        );

        $this->productoSeleccionado = null;
    }
    // Buscador → agrega a Lista B con badge si viene de otra categoria/subcategoria
    public function agregarProducto(int $id, string $nombre, ?string $categoria, ?int $subcategoriaId): void
    {
        if (collect($this->productosModal)->contains('id', $id))
            return;

        $this->productosModal[] = [
            'id' => $id,
            'nombre' => $nombre,
            'categoria_codigo' => $categoria,
            'subcategoria_id' => $subcategoriaId,
            'es_nuevo' => true,
            'categoria_anterior' => $categoria !== $this->categoriaCodigo ? $categoria : null,
        ];
    }

    public function quitarProducto(int $id): void
    {
        $this->productosModal = collect($this->productosModal)
            ->reject(fn($p) => $p['id'] === $id)
            ->values()->toArray();
    }

    public function guardarProductos(): void
    {
        $idsEnLista = collect($this->productosModal)->pluck('id');

        // Asignar categoria + subcategoria a todos los de la lista
        if ($idsEnLista->isNotEmpty()) {
            Producto::whereIn('id', $idsEnLista)->update([
                'subcategoria_id' => $this->subcategoriaActivaId,
                'categoria_codigo' => $this->categoriaCodigo,
            ]);
        }

        // Todo producto que tenga esta subcategoria o esta categoria
        // pero NO esté en la lista → limpiar ambos campos
        Producto::where(function ($q) {
            $q->where('subcategoria_id', $this->subcategoriaActivaId)
                ->where('categoria_codigo', $this->categoriaCodigo);
        })
            ->whereNotIn('id', $idsEnLista->isEmpty() ? [0] : $idsEnLista)
            ->update([
                'subcategoria_id' => null,
                'categoria_codigo' => null,
            ]);

        $this->modalProductos = false;
        $this->productosModal = [];
        $this->mount();
        $this->alert('success', 'Productos actualizados correctamente');
    }

    public function getProductos(string $search): array
    {
        return app(ProductoServicio::class)->buscar($search);
    }
    public function render()
    {
        return view('livewire.gestion-categorias.categorias-component');
    }
}
<?php

namespace App\Livewire\GestionCategorias;

use App\Models\InsCategoria;
use App\Models\InsSubcategoria;
use App\Services\Categoria\SubcategoriaServicio;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;

class SubcategoriasComponent extends Component
{
    use LivewireAlert;
    // ─── Estado tabla ──────────────────────────────────────────
    public array $filasModificadas = [];

    // ─── Auditoría ─────────────────────────────────────────────
    public bool $modalAuditoria = false;
    public array $auditoriaHistorial = [];

    // ─── Datos para la vista ───────────────────────────────────
    public array $registros = [];
    public array $listaCategorias = [];

    public function mount(): void
    {
        $this->listaCategorias = InsCategoria::orderBy('descripcion')
            ->pluck('descripcion', 'codigo')
            ->toArray();

        $this->cargarRegistros();
    }

    private function cargarRegistros(): void
    {
        $this->registros = InsSubcategoria::with('categoria')
            ->withCount('productos')
            ->orderBy('categoria_codigo')
            ->orderBy('nombre')
            ->get()
            ->map(fn($s) => [
                'id' => $s->id,
                'categoria_codigo' => $s->categoria_codigo,
                'nombre' => $s->nombre,
                'descripcion' => $s->descripcion,

                'cantidad_productos' => $s->productos_count,
            ])
            ->toArray();
    }

    // ─── Guardar filas modificadas ─────────────────────────────
    public function guardarSubcategorias(array $filas): void
    {
        try {
            foreach ($filas as $fila) {
                // Fila vacía (spare row de Handsontable)
                if (empty($fila['nombre']) || empty($fila['categoria_codigo'])) {
                    continue;
                }

                SubcategoriaServicio::guardar(
                    data: [
                        'categoria_codigo' => $fila['categoria_codigo'],
                        'nombre' => $fila['nombre'],
                        'descripcion' => $fila['descripcion'] ?? null,
                    ],
                    subcategoriaId: $fila['id'] ?? null,
                );
            }

            $this->filasModificadas = [];
            $this->cargarRegistros();
            $this->dispatch('cargarDataSubcategorias', data: $this->registros);
            $this->alert('success', 'Subcategorías guardadas.');
        } catch (\Throwable $th) {
            $this->alert('error', $th->getMessage());
        }
    }

    // ─── Eliminar ──────────────────────────────────────────────
    public function eliminar(int $id): void
    {
        try {
            SubcategoriaServicio::eliminar($id);
            $this->cargarRegistros();
            $this->dispatch('cargarDataSubcategorias', data: $this->registros);
            $this->alert('success', 'Subcategoría eliminada.');
        } catch (\Throwable $th) {
            $this->alert('error', $th->getMessage());
        }
    }

    // ─── Auditoría ─────────────────────────────────────────────
    public function verAuditoria(int $id): void
    {
        $this->auditoriaHistorial = SubcategoriaServicio::getAuditoria($id);
        $this->modalAuditoria = true;
    }

    public function render()
    {
        return view('livewire.gestion-categorias.subcategorias-component', [
            'registros' => $this->registros,
            'listaCategorias' => $this->listaCategorias,
        ]);
    }
}
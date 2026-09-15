<?php

namespace App\Livewire;

use App\Models\AlmacenProductoSalida;
use App\Models\Campo;
use App\Models\Maquinaria;
use App\Models\Producto;
use App\Services\AlmacenServicio;
use App\Services\AuditoriaServicio;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;
use Livewire\WithPagination;

class AlmacenSalidaListaComponent extends Component
{
    use LivewireAlert, WithPagination;

    public $anio;
    public $mes;
    public string $tipo;

    public array $filtros = [
        'dia' => '',
        'productoId' => '',
        'destinoId' => '',
        'categoria' => '',
    ];

    public array $seleccionados = [];
    public bool $modalAuditoriaSalida = false;
    public array $auditoriaHistorialSalida = [];

    public array $listaProductos = [];
    public array $listaMaquinarias = [];
    public array $listaCampos = [];

    protected $listeners = [
        'salidaGuardada' => 'alSalidaGuardada',
    ];

    public function mount($mes = null, $anio = null, string $tipo)
    {
        $this->mes = $mes;
        $this->anio = $anio;
        $this->tipo = $tipo;
        $this->cargarListas();
    }

    public function updatingFiltros(): void
    {
        $this->resetPage();
        $this->seleccionados = [];
    }

    public function limpiarFiltros(): void
    {
        $this->filtros = ['dia' => '', 'productoId' => '', 'destinoId' => '', 'categoria' => ''];
        $this->resetPage();
        $this->seleccionados = [];
    }

    public function toggleSeleccionTodos(bool $marcar, array $idsPaginaActual): void
    {
        if ($marcar) {
            $this->seleccionados = array_values(array_unique(array_merge($this->seleccionados, $idsPaginaActual)));
        } else {
            $this->seleccionados = array_values(array_diff($this->seleccionados, $idsPaginaActual));
        }
    }

    // ────────────────────────────────────────────────────────────
    // Abrir el modal de formulario (crear o editar según haya selección)
    // ────────────────────────────────────────────────────────────
    public function abrirCrear(): void
    {
        $this->dispatch('abrirFormularioSalida', tipo: $this->tipo, mes: $this->mes, anio: $this->anio, ids: []);
    }

    public function abrirEditarSeleccionados(): void
    {
        if (empty($this->seleccionados)) {
            $this->alert('warning', 'Selecciona al menos una fila.');
            return;
        }
        $this->dispatch('abrirFormularioSalida', tipo: $this->tipo, mes: $this->mes, anio: $this->anio, ids: $this->seleccionados);
    }

    /**
     * El formulario avisa con qué mes/año quedó el registro guardado —
     * saltamos automáticamente a ese periodo para que el usuario vea
     * de inmediato lo que acaba de guardar, aunque estuviera viendo otro mes.
     */
    public function alSalidaGuardada($mes, $anio): void
    {
        $this->mes = $mes;
        $this->anio = $anio;
        $this->seleccionados = [];
        $this->resetPage();
    }

    public function verHistorialSalida(int $id): void
    {
        $this->auditoriaHistorialSalida = AuditoriaServicio::getAuditoria(AlmacenProductoSalida::class, $id);
        $this->modalAuditoriaSalida = true;
    }

    public function eliminarSalida(int $id): void
    {
        try {
            $salida = AlmacenProductoSalida::findOrFail($id);

            AuditoriaServicio::registrar(
                modelo: AlmacenProductoSalida::class,
                modeloId: $salida->id,
                accion: 'eliminar',
                antes: $salida->toArray(),
                camposIgnorados: ['creado_por', 'editado_por', 'created_at', 'updated_at'],
            );

            $salida->delete();
            $this->seleccionados = array_values(array_diff($this->seleccionados, [$id]));
            $this->alert('success', 'Salida eliminada.');
        } catch (\Exception $e) {
            $this->alert('error', $e->getMessage());
        }
    }

    public function eliminarSeleccionados(): void
    {
        if (empty($this->seleccionados)) {
            $this->alert('warning', 'Selecciona al menos una fila.');
            return;
        }

        foreach ($this->seleccionados as $id) {
            $this->eliminarSalida($id);
        }
        $this->seleccionados = [];
    }

    public function cargarListas(): void
    {
        $this->listaCampos = Campo::get()->map(fn($p) => ['id' => $p->nombre, 'label' => $p->nombre])->toArray();
        $this->listaProductos = Producto::deTipo($this->tipo)->get()
            ->map(fn($p) => ['id' => $p->id, 'label' => $p->nombre_comercial])->toArray();

        if ($this->tipo === 'combustible') {
            $this->listaMaquinarias = Maquinaria::orderBy('nombre')->get()
                ->map(fn($m) => ['id' => $m->id, 'label' => $m->nombre])->toArray();
        }
    }

    public function render()
    {
        $salidas = AlmacenServicio::obtenerRegistrosPorFecha(
            $this->mes,
            $this->anio,
            $this->tipo,
            null,
            10,
            $this->filtros,
        );

        return view('livewire.almacen-salida-lista-component', [
            'salidas' => $salidas,
        ]);
    }
}
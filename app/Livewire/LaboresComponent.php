<?php

namespace App\Livewire;

use App\Models\Labores;
use App\Models\ManoObra;
use App\Services\RecursosHumanos\Personal\ActividadServicio;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;
use Livewire\WithoutUrlPagination;
use Livewire\WithPagination;

class LaboresComponent extends Component
{
    use WithPagination;
    use WithoutUrlPagination;
    use LivewireAlert;
    public $verActivos;
    public $laborId;
    public $search = '';
    public $mostrarFormularioLabor = false;
    public $codigo;
    public $nombre_labor;
    public $estandar_produccion;
    public $unidades;
    public $estado;
    public $codigo_mano_obra;
    public $manoObras;
    public $manoObraFiltro;
    // App/Http/Livewire/MiComponente.php
    public array $tramos = [
        ['hasta' => '', 'monto' => '']
    ];

    protected $listeners = ['laborRegistrada' => '$refresh', 'confirmarEliminar', 'valoracionTrabajada' => '$refresh'];
    public function mount()
    {
        $this->manoObras = ManoObra::all();
    }
    public function crearNuevaLabor()
    {
        $this->resetearCampo();
        $this->mostrarFormularioLabor = true;
    }
    public function editarLabor($laborId)
    {
        $this->laborId = $laborId;
        $labor = Labores::find($this->laborId);
        if ($labor) {
            $this->codigo = $labor->codigo;
            $this->nombre_labor = $labor->nombre_labor;
            $this->estandar_produccion = $labor->estandar_produccion;
            $this->unidades = $labor->unidades;
            $this->estado = $labor->estado;
            $this->codigo_mano_obra = $labor->codigo_mano_obra;
            $this->tramos = $labor->tramos_bonificacion != null ? json_decode($labor->tramos_bonificacion, true) : [['hasta' => '', 'monto' => '']];
            $this->mostrarFormularioLabor = true;
        } else {
            $this->alert('error', 'Labor no encontrada.');
            return;
        }
    }
    public function guardarLabor()
    {
        $this->validate([
            'codigo' => 'required|integer',
            'nombre_labor' => 'required|string|max:255',
            'tramos.*.hasta' => 'nullable|numeric|min:0',
            'tramos.*.monto' => 'nullable|numeric|min:0',
            'codigo_mano_obra' => 'nullable|exists:mano_obras,codigo',
        ]);
        try {
            // Preparar datos
            $data = [
                'codigo' => $this->codigo,
                'nombre_labor' => $this->nombre_labor,
                'estandar_produccion' => $this->estandar_produccion,
                'unidades' => $this->unidades,
                'tramos_bonificacion' => empty($this->tramos) ? null : json_encode($this->tramos),
                'estado' => $this->estado ?? 1,
                'codigo_mano_obra' => $this->codigo_mano_obra,
            ];

            ActividadServicio::guardarLabor($data, $this->laborId);

            $this->resetearCampo();
            $this->alert('success', 'Labor guardada correctamente.');

        } catch (\Throwable $th) {
            $this->alert('error', $th->getMessage());
        }
    }
    public function resetearCampo()
    {
        $this->reset(['laborId', 'codigo', 'nombre_labor', 'codigo_mano_obra', 'estandar_produccion', 'unidades', 'tramos', 'estado']);
        $this->mostrarFormularioLabor = false;
    }
    public function confirmarEliminarLabor($id)
    {
        $this->confirm('¿Está seguro(a) que desea eliminar el registro?.', [
            'onConfirmed' => 'confirmarEliminar',
            'data' => [
                'laborId' => $id,
            ],
        ]);
    }
    public function confirmarEliminar($data)
    {
        try {
            ActividadServicio::eliminarLabor($data['laborId']);
            $this->alert('success', 'Registro eliminado correctamente.');
        } catch (\Throwable $th) {
            $this->alert('error', $th->getMessage());
        }
    }
    public function updatingSearch()
    {
        $this->resetPage();
    }
    public function habilitar($laborId, $estado)
    {
        try {
            ActividadServicio::habilitarLabor($laborId, $estado);
            $this->alert('success', 'Registro actualizado correctamente.');
        } catch (\Throwable $th) {
            $this->alert('error', $th->getMessage());
        }
    }
    public function render()
    {
        $query = Labores::query();
        if ($this->verActivos === '0') {
            $query->where('estado', '0');
        } elseif ($this->verActivos === '1') {
            $query->where('estado', '1');
        }
        if ($this->search && $this->manoObraFiltro) {
            $query->where(function ($q) {
                $q->where('codigo_mano_obra', $this->manoObraFiltro)
                    ->orWhere(function ($q2) {
                        $q2->where('codigo', 'like', '%' . $this->search . '%')
                            ->orWhere('nombre_labor', 'like', '%' . $this->search . '%');
                    });
            });
        } elseif ($this->manoObraFiltro) {
            $query->where('codigo_mano_obra', $this->manoObraFiltro);
        } elseif ($this->search) {
            $query->where(function ($q) {
                $q->where('codigo', 'like', '%' . $this->search . '%')
                    ->orWhere('nombre_labor', 'like', '%' . $this->search . '%');
            });
        }


        $labores = $query->paginate(10);

        return view('livewire.labores-component', [
            'labores' => $labores
        ]);
    }
}

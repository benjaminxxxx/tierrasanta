<?php

namespace App\Livewire;

use App\Models\Labores;
use App\Services\RecursosHumanos\Personal\ActividadServicio;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;
use Livewire\WithPagination;

class LaboresComponent extends Component
{
    use WithPagination;
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
    // App/Http/Livewire/MiComponente.php
    public array $tramos = [
        ['hasta' => '', 'monto' => '']
    ];

    protected $listeners = ['laborRegistrada' => '$refresh', 'confirmarEliminar', 'valoracionTrabajada' => '$refresh'];
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
            $this->tramos = $labor->tramos_bonificacion!=null ? json_decode($labor->tramos_bonificacion, true) : [['hasta' => '', 'monto' => '']];
            $this->mostrarFormularioLabor = true;
        }else{
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
        ]);
        try {
            // Preparar datos
            $data = [
                'codigo' => $this->codigo,
                'nombre_labor' => $this->nombre_labor,
                'estandar_produccion' => $this->estandar_produccion,
                'unidades' => $this->unidades,
                'tramos_bonificacion' => empty($this->tramos) ? null : json_encode($this->tramos),
                'estado' => $this->estado ?? 1
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
        $this->reset(['laborId', 'codigo', 'nombre_labor', 'estandar_produccion', 'unidades', 'tramos', 'estado']);
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
        //buscar ahora por codigo o descripcion $query->where('nombre_labor', 'like', '%' . $this->search . '%');
        $query->where(function ($q) {
            $q->where('codigo', 'like', '%' . $this->search . '%')
              ->orWhere('nombre_labor', 'like', '%' . $this->search . '%');
        });
        $labores = $query->paginate(10);

        return view('livewire.labores-component', [
            'labores' => $labores
        ]);
    }
}

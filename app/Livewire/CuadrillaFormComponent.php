<?php

namespace App\Livewire;

use App\Models\Cuadrillero;
use App\Models\CuaGrupo;
use App\Models\GruposCuadrilla;
use App\Services\Cuadrilla\CuadrilleroServicio;
use Illuminate\Database\QueryException;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;

class CuadrillaFormComponent extends Component
{
    use LivewireAlert;
    public $mostrarFormulario = false;
    public $nombres;
    public $dni;
    public $codigo_grupo;
    public $cuadrilleroId = null;
    public $grupos = [];
    protected $listeners = ["registrarCuadrillero", 'editarCuadrillero'];
    public function mount()
    {
        $this->grupos = CuadrilleroServicio::obtenerGrupos();
    }

    public function registrar()
    {
        $this->validate([
            'nombres' => 'required|string|max:255',
            'dni' => 'required|string|max:20|unique:cuadrilleros,dni,' . $this->cuadrilleroId,
        ], [
            'nombres.required' => 'El nombre es obligatorio',
            'dni.required' => 'El dni es obligatorio',
            'dni.unique' => 'El dni ya está siendo utilizado',
        ]);

        $data = [
            'nombres' => $this->nombres,
            'dni' => $this->dni,
            'codigo_grupo' => $this->codigo_grupo !== '' ? $this->codigo_grupo : null,
        ];

        try {
            $cuadrillero = CuadrilleroServicio::guardarCuadrillero($data, $this->cuadrilleroId);

            if ($this->cuadrilleroId) {
                $this->alert('success', 'Cuadrillero actualizado exitosamente.');
            } else {
                $this->alert('success', 'Cuadrillero registrado exitosamente.');
            }

            $this->mostrarFormulario = false;
            $this->resetForm();
            $this->dispatch('cuadrilleroRegistrado', $cuadrillero);
        } catch (QueryException $e) {
            $this->alert('error', 'Hubo un error al guardar el cuadrillero: ' . $e->getMessage());
        }
    }
    public function registrarCuadrillero()
    {
        $this->resetForm();
        $this->mostrarFormulario = true;
    }
    public function editarCuadrillero($cuadrilleroId)
    {
        $this->resetForm();
        $cuadrillero = Cuadrillero::find($cuadrilleroId);
        if ($cuadrillero) {
            $this->cuadrilleroId = $cuadrilleroId;
            $this->nombres = $cuadrillero->nombres;
            $this->dni = $cuadrillero->dni;
            $this->codigo_grupo = $cuadrillero->codigo_grupo;
            $this->mostrarFormulario = true;
        }

    }

    private function resetForm()
    {
        $this->resetErrorBag();
        $this->reset(['cuadrilleroId', 'nombres', 'dni', 'codigo_grupo']);
    }
    public function render()
    {
        return view('livewire.cuadrilla-form-component');
    }
}

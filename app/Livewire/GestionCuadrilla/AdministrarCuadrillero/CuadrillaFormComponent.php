<?php

namespace App\Livewire\GestionCuadrilla\AdministrarCuadrillero;

use App\Models\Cuadrillero;
use App\Services\Cuadrilla\CuadrilleroServicio;
use App\Traits\ListasComunes\ConGrupoCuadrilla;
use Illuminate\Database\QueryException;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;

class CuadrillaFormComponent extends Component
{
    use LivewireAlert, ConGrupoCuadrilla;
    public $mostrarFormularioCuadrillero = false;
    public $nombres;
    public $dni;
    public $grupoSeleccionado;
    public $cuadrilleroId = null;
    protected $listeners = ["registrarCuadrillero", 'editarCuadrillero'];

    public function guardarCuadrillero()
    {

        try {

            $data = [
                'nombres' => $this->nombres,
                'dni' => $this->dni,
                'codigo_grupo' => $this->grupoSeleccionado,
            ];

            $cuadrillero = app(CuadrilleroServicio::class)->guardar($data, $this->cuadrilleroId);

            if ($this->cuadrilleroId) {
                $this->alert('success', 'Cuadrillero actualizado exitosamente.');
            } else {
                $this->alert('success', 'Cuadrillero registrado exitosamente.');
            }

            $this->mostrarFormularioCuadrillero = false;
            $this->resetForm();
            $this->dispatch('cuadrilleroRegistrado', $cuadrillero);
        } catch (QueryException $e) {
            $this->alert('error', 'Hubo un error al guardar el cuadrillero: ' . $e->getMessage());
        }
    }
    public function registrarCuadrillero()
    {
        $this->resetForm();
        $this->mostrarFormularioCuadrillero = true;
    }
    public function editarCuadrillero($cuadrilleroId)
    {
        $this->resetForm();
        $cuadrillero = Cuadrillero::find($cuadrilleroId);
        if ($cuadrillero) {
            $this->cuadrilleroId = $cuadrilleroId;
            $this->nombres = $cuadrillero->nombres;
            $this->dni = $cuadrillero->dni;
            $this->grupoSeleccionado = $cuadrillero->codigo_grupo;
            $this->mostrarFormularioCuadrillero = true;
        }
    }
    private function resetForm()
    {
        $this->resetErrorBag();
        $this->reset(['cuadrilleroId', 'nombres', 'dni', 'grupoSeleccionado']);
    }
    public function render()
    {
        return view('livewire.gestion-cuadrilla.administrar-cuadrillero.cuadrilla-form-component');
    }
}

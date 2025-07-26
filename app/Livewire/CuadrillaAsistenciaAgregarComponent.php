<?php

namespace App\Livewire;

use App\Models\CuaAsistenciaSemanalCuadrillero;
use App\Models\CuaAsistenciaSemanalGrupo;
use App\Models\CuadrillaAsistenciaCuadrillero;
use App\Models\CuadrillaAsistenciaGrupo;
use App\Models\Cuadrillero;
use App\Models\CuaGrupo;
use App\Services\Cuadrilla\CuadrilleroServicio;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;

class CuadrillaAsistenciaAgregarComponent extends Component
{
    use LivewireAlert;
    public $mostrarAgregarCuadrillero = false;
    public $search;
    public $results = [];
    public $cuadrillerosAgregados = [];
    public $fecha;
    public $grupos = [];
    public $codigo_grupo;
    public $listaCuadrilleros = [];
    protected $listeners = ['agregarCuadrilleros', 'cuadrilleroRegistrado', 'cuadrilleroRegistradoDeEmpleados', 'grupoRegistrado'];
    public function mount()
    {
        $this->grupos = CuaGrupo::where('estado', true)->get();
        if ($this->grupos->isNotEmpty()) {
            $this->codigo_grupo = $this->grupos->first()->codigo;
        }
        $this->listaCuadrilleros = Cuadrillero::where('estado', true)
            ->select('id', 'nombres', 'dni')
            ->orderBy('nombres')
            ->get()
            ->toArray();
    }
    public function agregarListaAgregada()
    {
        try {
            
            $grupoBase = CuaGrupo::where('codigo', $this->codigo_grupo)->first();

            if ($grupoBase) {
                $fechaInicio = $this->fecha;
                $rows = [];
                foreach ($this->cuadrillerosAgregados as $cuadrillero) {

                    $rows[] = [
                        'cuadrillero_nombres' => $cuadrillero['nombres'],
                        'codigo_grupo' => $this->codigo_grupo,
                        'cuadrillero_id' => $cuadrillero['id']
                    ];
                }
                
                $lista = CuadrilleroServicio::registrarOrdenSemanal($fechaInicio, $rows);
            }

            $this->dispatch('cuadrillerosAgregadosAsistencia');
            $this->mostrarAgregarCuadrillero = false;
            $this->resetForm();
        } catch (\Throwable $th) {
            $this->alert('error', $th->getMessage());
        }
    }
    public function resetForm()
    {
        $this->resetErrorBag();
        $this->search = null;
        $this->results = [];
        $this->cuadrillerosAgregados = [];
        $this->fecha = null;
        $this->codigo_grupo = null;
    }
    public function cuadrilleroRegistrado($cuadrillero)
    {
        $this->agregarCuadrillero($cuadrillero['id']);
    }
    public function cuadrilleroRegistradoDeEmpleados($cuadrilleros)
    {
        if (!is_array($cuadrilleros)) {
            return;
        }
        foreach ($cuadrilleros as $idCuadrillero) {
            $this->agregarCuadrillero($idCuadrillero);
        }

    }
    public function grupoRegistrado($grupo)
    {
        $this->grupos = CuaGrupo::where('estado', true)->get();
        if ($this->grupos->isNotEmpty()) {
            $this->codigo_grupo = $grupo['codigo'];
        }
    }
    public function agregarCuadrilleros($fecha)
    {
        $this->resetForm();
        $this->fecha = $fecha;
        $this->mostrarAgregarCuadrillero = true;
    }

    public function render()
    {
        return view('livewire.cuadrilla-asistencia-agregar-component');
    }
}

<?php

namespace App\Livewire;

use App\Models\CuaAsistenciaSemanalCuadrillero;
use App\Models\CuaAsistenciaSemanalGrupo;
use App\Models\CuadrillaAsistenciaCuadrillero;
use App\Models\CuadrillaAsistenciaGrupo;
use App\Models\Cuadrillero;
use App\Models\CuaGrupo;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;

class CuadrillaAsistenciaAgregarComponent extends Component
{
    use LivewireAlert;
    public $mostrarAgregarCuadrillero = false;
    public $search;
    public $results = [];
    public $cuadrillerosAgregados = [];
    public $cuadrilla_asistencia_id;
    public $grupos = [];
    public $codigo_grupo;
    protected $listeners = ['agregarCuadrilleros', 'cuadrilleroRegistrado','grupoRegistrado'];
    public function mount(){
        $this->grupos = CuaGrupo::where('estado',true)->get();
        if ($this->grupos->isNotEmpty()) {
            $this->codigo_grupo = $this->grupos->first()->codigo; 
        }
    }
    public function agregarListaAgregada(){
       

        $grupoBase = CuaGrupo::where('codigo',$this->codigo_grupo)->first();

        if($grupoBase){
            $grupoEnAsistencia = CuaAsistenciaSemanalGrupo::where('gru_cua_cod',$this->codigo_grupo)
            ->where('cua_asi_sem_id',$this->cuadrilla_asistencia_id)->first();

            if(!$grupoEnAsistencia){
                $grupoEnAsistencia = CuaAsistenciaSemanalGrupo::create([
                    'cua_asi_sem_id' => $this->cuadrilla_asistencia_id,
                    'gru_cua_cod' => $grupoBase->codigo,
                    'costo_dia' => $grupoBase->costo_dia_sugerido,
                    'costo_hora' => (float)$grupoBase->costo_dia_sugerido>0?(float)$grupoBase->costo_dia_sugerido/8:0,
                ]);
            }
            if($grupoEnAsistencia){
                foreach ($this->cuadrillerosAgregados as $cuadrilleroId => $cuadrillero) {
                    $existe = CuaAsistenciaSemanalCuadrillero::where('cua_id',$cuadrilleroId)
                    ->where('cua_asi_sem_gru_id',$grupoEnAsistencia->id)
                    ->exists();
                    if($existe){
                        continue;
                    }

                    CuaAsistenciaSemanalCuadrillero::create([
                        'cua_id' => $cuadrilleroId,
                        'cua_asi_sem_gru_id' => $grupoEnAsistencia->id,
                        'monto_recaudado' => 0
                    ]);
                }
            }
            
        }
        
        $this->dispatch('cuadrillerosAgregadosAsistencia');
        $this->mostrarAgregarCuadrillero = false;
        $this->resetForm();
    }
    public function resetForm(){
        $this->resetErrorBag();
        $this->search = null;
        $this->results = [];
        $this->cuadrillerosAgregados = [];
        $this->cuadrilla_asistencia_id = null;
        $this->codigo_grupo = null;
    }
    public function cuadrilleroRegistrado($cuadrillero)
    {
        $this->agregarCuadrillero($cuadrillero['id']);
    }
    public function grupoRegistrado($grupo)
    {
        $this->grupos = CuaGrupo::where('estado',true)->get();
        if ($this->grupos->isNotEmpty()) {
            $this->codigo_grupo = $grupo['codigo'];
        }
    }
    public function agregarCuadrilleros($cuadrilla_asistencia_id)
    {
        $this->resetForm();
        $this->cuadrilla_asistencia_id = $cuadrilla_asistencia_id;
        
        $this->mostrarAgregarCuadrillero = true;
    }
    public function eliminarCuadrilleroAsistencia($cuadrilleroId)
    {
        unset($this->cuadrillerosAgregados[$cuadrilleroId]);
    }
    public function agregarCuadrillero($cuadrilleroId)
    {
        $cuadrillero = Cuadrillero::find($cuadrilleroId);

        // Si no se encuentra el cuadrillero, salir de la función
        if (!$cuadrillero) {
            return;
        }

        // Verificar si ya existe en CuadrillaAsistenciaCuadrillero con los parámetros especificados
        $existe = CuaAsistenciaSemanalCuadrillero::where('cua_id', $cuadrillero->id)
            ->where('cua_asi_sem_gru_id', $this->cuadrilla_asistencia_id)
            ->exists();

        // Si ya existe, salir de la función
        if ($existe) {
            return $this->alert('success', 'El cuadrillero ya está agregado en el mismo grupo.');
        }

        $this->cuadrillerosAgregados[$cuadrillero->id] = [
            'nombres' => $cuadrillero->nombres,
            'dni' => $cuadrillero->dni
        ];

        $this->results = [];
        $this->search = null;
    }
    public function updatedSearch()
    {
        $this->results = Cuadrillero::where(function ($query) {
            $query->where('nombres', 'like', '%' . $this->search . '%')
                ->orWhere('dni', 'like', '%' . $this->search . '%');
        })
        ->where('estado',true)
        ->get();
    }
    public function render()
    {
        return view('livewire.cuadrilla-asistencia-agregar-component');
    }
}

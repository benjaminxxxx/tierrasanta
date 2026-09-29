<?php

namespace App\Livewire;

use App\Models\CochinillaInfestacion;
use Livewire\Component;
use Livewire\WithPagination;

class CochinillaInfestacionComponent extends Component
{
    use WithPagination;
    public $aniosDisponibles = [];
    public $campoSeleccionado;
    public $campoSeleccionadoOrigen;
    public $tipoSeleccionado;
    public $anioSeleccionado;
    public $filtrarCarton = true;
    public $filtrarTubo = false;
    public $filtrarMalla = false;
    public $nuevoRegistro = null;
    protected $listeners = ['infestacionProcesada'];
    public function mount(){
        $this->aniosDisponibles = CochinillaInfestacion::selectRaw('YEAR(fecha) as anio')
            ->groupBy('anio')
            ->orderBy('anio', 'desc')
            ->pluck('anio')
            ->toArray();
    }
    public function infestacionProcesada($data){
        $this->resetPage();
        $metodo = $data['metodo']??'carton';
        if($metodo=='carton'){
            $this->filtrarCarton = true;
        }
        if($metodo=='tubo'){
            $this->filtrarTubo = true;
        }
        if($metodo=='malla'){
            $this->filtrarMalla = true;
        }
        $this->nuevoRegistro = $data['id']??null;
    }
    public function updatedFiltrarCarton(){
        $this->resetPage();
    }
    public function updatedFiltrarTubo(){
        $this->resetPage();
    }
    public function updatedFiltrarMalla(){
        $this->resetPage();
    }
    public function updatedCampoSeleccionado(){
        $this->resetPage();
    }
    public function updatedCampoSeleccionadoOrigen(){
        $this->resetPage();
    }
    public function render()
    {
        $metodos = [];

        if ($this->filtrarCarton) {
            $metodos[] = 'carton';
        }
        if ($this->filtrarTubo) {
            $metodos[] = 'tubo';
        }
        if ($this->filtrarMalla) {
            $metodos[] = 'malla';
        }

        $query = CochinillaInfestacion::orderBy('fecha', 'desc');

        if (!empty($metodos)) {
            $query->whereIn('metodo', $metodos);
        }
        if ($this->campoSeleccionado) {
            $query->where('campo_nombre', $this->campoSeleccionado);
        }
        if ($this->campoSeleccionadoOrigen) {
            $query->where('campo_origen_nombre', $this->campoSeleccionadoOrigen);
        }
        if ($this->anioSeleccionado) {
            $query->whereYear('fecha', $this->anioSeleccionado);
        }
        if ($this->tipoSeleccionado) {
            $query->where('tipo_infestacion', $this->tipoSeleccionado);
        }

        $cochinillaInfestaciones = $query->paginate(30);

        $cochinillaInfestaciones->getCollection()->transform(function ($infestacion) {
            // Inicializar todos los campos a null
            foreach (['carton', 'tubo', 'malla'] as $tipo) {
                $infestacion->{$tipo . '_capacidad_envase'} = null;
                $infestacion->{$tipo . '_numero_envases'} = null;
                $infestacion->{$tipo . '_infestadores'} = null;
                $infestacion->{$tipo . '_madres_por_infestador'} = null;
                $infestacion->{$tipo . '_infestadores_por_ha'} = null;
            }

            // Asignar solo en el tipo correspondiente
            $prefix = $infestacion->metodo; // 'carton', 'tubo' o 'malla'

            $infestacion->{$prefix . '_capacidad_envase'} = $infestacion->capacidad_envase;
            $infestacion->{$prefix . '_numero_envases'} = $infestacion->numero_envases;
            $infestacion->{$prefix . '_infestadores'} = $infestacion->infestadores;
            $infestacion->{$prefix . '_madres_por_infestador'} = $infestacion->madres_por_infestador_alias;
            $infestacion->{$prefix . '_infestadores_por_ha'} = $infestacion->infestadores_por_ha_alias;

            return $infestacion;
        });

        return view('livewire.cochinilla-infestacion-component', [
            'cochinillaInfestaciones' => $cochinillaInfestaciones
        ]);
    }
}

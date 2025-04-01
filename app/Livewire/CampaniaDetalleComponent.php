<?php

namespace App\Livewire;

use App\Models\CampoCampania as Campania;
use App\Models\PoblacionPlantas;
use App\Models\Siembra;
use App\Services\CampaniaServicio;
use Livewire\Component;

class CampaniaDetalleComponent extends Component
{
    public $campania;
    public $mostrarFormulario = false;
    protected $listeners = ['abrirCampaniaDetalle'];
    public function actualizarInformacionCampania(){
        if(!$this->campania){
            return $this->alert('error','La campaña ya no existe.');
        }
        $data = [];
        //POBLACION PLANTAS
        
        //Fecha de evaluación día cero
        //Nª de pencas madre día cero
        //Fecha de evaluación resiembra
        //Nª de pencas madre después de resiembra

        $evaluacionesPoblacionPlanta = $this->campania->poblacionPlantas;
        //obteniendo la primera evaluacion del dia cero
        if($evaluacionesPoblacionPlanta->count()>0){
            $evaluacionDiaCero = $evaluacionesPoblacionPlanta->where('tipo_evaluacion','dia_cero')->sortBy('fecha')->first();
            $evaluacionUltimaResiembra = $evaluacionesPoblacionPlanta->where('tipo_evaluacion','resiembra')->sortByDesc('fecha')->first();
            if($evaluacionDiaCero){
                $data['pp_dia_cero_fecha_evaluacion'] = $evaluacionDiaCero->fecha;
                $data['pp_dia_cero_numero_pencas_madre'] = $evaluacionDiaCero->promedio_plantas_ha;
            }
            if($evaluacionUltimaResiembra){
                $data['pp_resiembra_fecha_evaluacion'] = $evaluacionUltimaResiembra->fecha;
                $data['pp_resiembra_numero_pencas_madre'] = $evaluacionUltimaResiembra->promedio_plantas_ha;
            }
        }

        //BROTES POR PISO
        $evaluacionesBrotesXPiso = $this->campania->evaluacionBrotesXPiso()->orderBy('fecha','desc')->first();
        if ($evaluacionesBrotesXPiso) {
            $data['brotexpiso_fecha_evaluacion'] = $evaluacionesBrotesXPiso->fecha;
            $data['brotexpiso_actual_brotes_2piso'] = $evaluacionesBrotesXPiso->promedio_actual_brotes_2piso;
            $data['brotexpiso_brotes_2piso_n_dias'] = $evaluacionesBrotesXPiso->promedio_brotes_2piso_n_dias;
            $data['brotexpiso_actual_brotes_3piso'] = $evaluacionesBrotesXPiso->promedio_actual_brotes_3piso;
            $data['brotexpiso_brotes_3piso_n_dias'] = $evaluacionesBrotesXPiso->promedio_brotes_3piso_n_dias;
            $data['brotexpiso_actual_total_brotes_2y3piso'] = $evaluacionesBrotesXPiso->promedio_actual_total_brotes_2y3piso;
            $data['brotexpiso_total_brotes_2y3piso_n_dias'] = $evaluacionesBrotesXPiso->promedio_total_brotes_2y3piso_n_dias;
        }
        
        $this->campania->update($data);
        

        //PoblacionPlantas::where()

        $campaniaServicio = new CampaniaServicio($this->campania->id);
        $campaniaServicio->actualizarGastosyConsumos();
    }
    public function abrirCampaniaDetalle($campaniaId)
    {
        try {
            $campania = Campania::findOrFail($campaniaId);
            if ($campania) {
                $this->campania = $campania;
                $this->mostrarFormulario = true;
            } else {
                $this->alert('error', 'La campaña ya no existe.');
            }
        } catch (\Throwable $th) {
            $this->dispatch('log', $th->getMessage());
            $this->alert('error', 'Ocurrió un error al buscar la campaña.');
        }
    }
    public function render()
    {
        return view('livewire.campania-detalle-component');
    }
}

<?php

namespace App\Livewire;

use App\Models\Actividad;
use App\Models\CuaAsistenciaSemanal;
use App\Models\CuadrilleroActividad;
use App\Models\CuadrilleroActividadRecogida;
use App\Models\Recogidas;
use App\Services\CuadrillaServicio;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;

class CuadrillaAsistenciaLaboresCuadrillerosComponent extends Component
{
    use LivewireAlert;
    public $mostrarFormulario = false;
    public $actividad;
    public $cuadrillerosAgregados = [];
    public $cuadrillerosSeleccionados = [];
    protected $listeners = ['agregarCuadrillerosEnActividad'];
    public function listarCuadrilleros()
    {

        if (!$this->actividad) {
            throw new \Exception("No se encuentra la actividad.");
        }

        $cuadrillerosAgregados = CuaAsistenciaSemanal::cuadrillerosEnFecha($this->actividad->fecha)->toArray();


        foreach ($cuadrillerosAgregados as $indice => $cuadrilleroAgregado) {
            $cua_asi_sem_cua_id = $cuadrilleroAgregado['cua_asi_sem_cua_id'];

            $cuadrillerosAgregados[$indice]['total_bono'] = '-';

            $actividad = CuadrilleroActividad::where('cua_asi_sem_cua_id', $cua_asi_sem_cua_id)->where('actividad_id', $this->actividad->id)->first();
            if ($actividad) {
                $cuadrillerosAgregados[$indice]['total_bono'] = $actividad->total_bono;
                $recogidas = $actividad->recogidas()->pluck('kg_logrados', 'recogida_id');

                $this->cuadrillerosSeleccionados[$cua_asi_sem_cua_id] = [
                    'trabajo' => true,
                    'recogida' => $recogidas,
                ];
            }
        }
        $this->cuadrillerosAgregados = $cuadrillerosAgregados;
    }
    public function agregarCuadrillerosEnActividad($actividadId)
    {
        $this->actividad = null;
        $this->cuadrillerosAgregados = [];
        $this->cuadrillerosSeleccionados = [];
        $this->actividad = Actividad::find($actividadId);
        if ($this->actividad) {
            $this->listarCuadrilleros();

            $this->mostrarFormulario = true;
        }
    }

    public function registrarCuadrilleros()
    {
        if (!$this->actividad) {
            return;
        }

        try {
            
            foreach ($this->cuadrillerosSeleccionados as $cua_asi_sem_cua_id => $cuadrilleroSeleccionado) {

                $trabajo = $cuadrilleroSeleccionado['trabajo'] ?? false;
                $recogidas = $cuadrilleroSeleccionado['recogida'] ?? [];
                if ($trabajo) {
                    $cuadrilleroActividad = CuadrilleroActividad::updateOrCreate([
                        'cua_asi_sem_cua_id' => $cua_asi_sem_cua_id,
                        'actividad_id' => $this->actividad->id,
                    ], [

                        'total_bono' => 0,
                        'total_costo' => 0,
                    ]);

                    if (count($recogidas) > 0) {
                        
                        foreach ($recogidas as $recogidaId => $cantidad) {
                            $cantidad = (float)$cantidad;
                            
                            $recogida = Recogidas::find($recogidaId);
                            if (!$recogida) {
                                continue;
                            }
                            if($cantidad<=0){
                                CuadrilleroActividadRecogida::where('cuadrillero_actividad_id',$cuadrilleroActividad->id)
                                ->where('recogida_id',$recogidaId)
                                ->delete();
                                continue;
                            }

                            $bono = 0;
                            if($this->actividad->valoracion){
                                $bono = ($cantidad - $recogida->kg_estandar) * $this->actividad->valoracion->valor_kg_adicional;
                            }
                            
                            CuadrilleroActividadRecogida::updateOrCreate([
                                'cuadrillero_actividad_id' => $cuadrilleroActividad->id,
                                'recogida_id' => $recogidaId,
                            ], [

                                'kg_logrados' => $cantidad,
                                'bono' => $bono
                            ]);
                        }
                    } else {
                        $cuadrilleroActividad->recogidas()->delete();
                    }

                    $sumaBono = $cuadrilleroActividad->recogidas()->sum('bono');
                    $bonoTotal = $sumaBono > 0 ? $sumaBono : 0;
                    $cuadrilleroActividad->update([
                        'total_bono' => $bonoTotal
                    ]);
                    
                    
                }else{
                    CuadrilleroActividad::where('cua_asi_sem_cua_id',$cua_asi_sem_cua_id)
                        ->where('actividad_id',$this->actividad->id)
                        ->delete();
                    
                }
            }


            $cuaAsistenciaSemanal = CuaAsistenciaSemanal::buscarSemana($this->actividad->fecha);
            if($cuaAsistenciaSemanal){
                $cuaAsistenciaSemanal->actualizarTotales();
            }

            $this->listarCuadrilleros();
            
            $this->dispatch('cuadrillerosAgregadosAsistencia');
            $this->alert('success', 'Cuadrilleros agregados a la labor correctamente.');
        } catch (\Throwable $th) {
            $this->dispatch('log', $th->getMessage());
            $this->alert('error', 'Ocurrió un error interno CALCC RC.');
        }
    }

    public function render()
    {
        return view('livewire.cuadrilla-asistencia-labores-cuadrilleros-component');
    }
}

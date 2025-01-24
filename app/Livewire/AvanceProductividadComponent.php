<?php

namespace App\Livewire;

use App\Models\RegistroProductividad;
use App\Services\CuadrillaServicio;
use App\Services\PlanillaServicio;
use App\Services\ProductividadServicio;
use Carbon\Carbon;
use Illuminate\Support\Facades\Session;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;

class AvanceProductividadComponent extends Component
{
    use LivewireAlert;
    public $empleados;
    public $fecha;
    public $reportesPorDia;
    protected $listeners = ['nuevoRegistroAvance' => '$refresh', 'confirmarEliminar'];
    public function mount()
    {
        //$this->fecha = Carbon::now()->format("Y-m-d");

        $this->fecha = Session::get('fecha_reporte', Carbon::now()->format('Y-m-d'));
    }
    public function fechaAnterior()
    {
        $this->fecha = Carbon::parse($this->fecha)->subDay()->format('Y-m-d');
        Session::put('fecha_reporte', $this->fecha);
    }

    public function fechaPosterior()
    {
        $this->fecha = Carbon::parse($this->fecha)->addDay()->format('Y-m-d');
        Session::put('fecha_reporte', $this->fecha);
    }
    public function updatedFecha()
    {
        Session::put('fecha_reporte', $this->fecha);
    }
    public function preguntarEliminar($registroId)
    {
        $this->alert('question', '¿Está seguro(a) que desea eliminar el registro?', [
            'showConfirmButton' => true,
            'confirmButtonText' => 'Si, Eliminar',
            'cancelButtonText' => 'Cancelar',
            'onConfirmed' => 'confirmarEliminar',
            'showCancelButton' => true,
            'position' => 'center',
            'toast' => false,
            'timer' => null,
            'confirmButtonColor' => '#056A70',
            'cancelButtonColor' => '#2C2C2C',
            'data' => [
                'registroId' => $registroId,
            ],
        ]);
    }
    public function confirmarEliminar($data)
    {
        /**
         * Explicacion del el siguiente algoritmo:
         * al eliminar un registro de productividad, debemos quitar los bonos que ya se habian agregado en esta lista de empleados y cuadrilleros
         * pero si se hac eun borrado normal, no se podria actualizar el descuento porque se borra el registro de lo que se borro
         * a veces en un mismo dia, hay varios registro para diferentes actividades, y al eliminar uno no se puede simplemente poner en 0, porque existe otros registros cuyos bonos se suamn
         * tambien se noto que en un registor pueden haber 10 empleados y en otro registor 2 de ellos pueden no estar, pero como se borraron esos registros...
         * no se puede saber quienes fueron, por eso antes de eliminar la lista se almacena en dataEmpleadosPoREliminar
         * 
         */
        $registroId = $data['registroId'];
        $registroProductividad = RegistroProductividad::find($registroId);
        if ($registroProductividad) {
            $fecha = $registroProductividad->fecha;

            $productividadServicioPorEliminar = new ProductividadServicio($registroProductividad->id);
            $dataEmpleadosPoREliminar = $productividadServicioPorEliminar->listarProductividadServicio();
            $registroProductividad->delete();
            $otrosRegistrosEnEseDia = RegistroProductividad::whereDate('fecha', $fecha)->get();

            if ($otrosRegistrosEnEseDia->count() > 0) {
                foreach ($otrosRegistrosEnEseDia as $otrosRegistroEnEseDia) {

                    $productividadServicioPorRevisar = new ProductividadServicio($otrosRegistroEnEseDia->id);
                    $dataEmpleadosPorRevisar = collect($productividadServicioPorRevisar->listarProductividadServicio());
                    $dniExistentes = $dataEmpleadosPorRevisar->pluck('dni')->toArray();
                    if(count($dataEmpleadosPoREliminar)>0){
                        foreach ($dataEmpleadosPoREliminar as $dataEmpleadoPoREliminar) {
                            if(!in_array($dataEmpleadoPoREliminar['dni'],$dniExistentes)){
                                if ($dataEmpleadoPoREliminar['tipo'] == 'planilla') {
                                    PlanillaServicio::quitarBono($dataEmpleadoPoREliminar['dni'], $fecha);
                                }
                            }
                        }
                    }
                    

                    $productividadId = $otrosRegistroEnEseDia->id;
                    $productividadServicio = new ProductividadServicio($productividadId);
                    $productividadServicio->registrarBonos();
                   
                }
            } else {
                //en ese dia no hay mas registros, quiere decir que se esta eliminando el ultimo, por ende se deben quitar todos los bonos de cada registro alli

                foreach ($dataEmpleadosPoREliminar as $dato) {

                    if ($dato['tipo'] == 'planilla') {
                        PlanillaServicio::quitarBono($dato['dni'], $fecha);
                    }
                }
            }
        }
        //->delete();
        $this->alert('success', 'Registro Eliminado Correctamente.');
    }
    public function render()
    {
        $this->reportesPorDia = RegistroProductividad::whereDate('fecha', $this->fecha)->get();
        return view('livewire.avance-productividad-component');
    }
}

<?php

namespace App\Livewire\Evaluaciones;

use App\Models\CampoCampania;
use App\Services\Produccion\Planificacion\CampaniaServicio;
use App\Support\CalculoHelper;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;
use Exception;

class EvaluacionReinfestacionFormComponent extends Component
{
    use LivewireAlert;

    public $mostrarFormularioEvalReinfestacion = false;

    public $reinfestacion_fecha;
    public $reinfestacion_fecha_recojo_vaciado_infestadores;
    public $reinfestacion_fecha_colocacion_malla;
    public $reinfestacion_fecha_retiro_malla;

    public $campania;

    protected $listeners = ['editarReinfestacion', 'sincronizarReinformacionInfestacion'];

    public function mount()
    {
    }
    public function sincronizarReinformacionInfestacion($campaniaId)
    {
        try {

            $campaniaServicio = new CampaniaServicio();
            $campaniaServicio->registrarHistorialDeInfestaciones($campaniaId, 'reinfestacion');

            $this->dispatch('refrescarInformeCampaniaXCampo');
            $this->alert('success', 'Datos sincronizados correctamente');
        } catch (\Throwable $th) {
            $this->alert('error', $th->getMessage());
        }
    }
    public function editarReinfestacion($campaniaId)
    {
        $campania = CampoCampania::find($campaniaId);

        if (!$campania) {
            $this->alert('error', 'Campaña no encontrada');
            return;
        }

        $this->campania = $campania;

        // Cargar valores actuales
        $this->reinfestacion_fecha = $campania->reinfestacion_fecha;
        $this->reinfestacion_fecha_recojo_vaciado_infestadores = $campania->reinfestacion_fecha_recojo_vaciado_infestadores;
        $this->reinfestacion_fecha_colocacion_malla = $campania->reinfestacion_fecha_colocacion_malla;
        $this->reinfestacion_fecha_retiro_malla = $campania->reinfestacion_fecha_retiro_malla;

        $this->mostrarFormularioEvalReinfestacion = true;
    }

    public function guardarEvaluacionReinfestacion()
    {
        try {
            $reinfestacionFecha = $this->reinfestacion_fecha !== '' ? $this->reinfestacion_fecha : null;

            $this->campania->reinfestacion_fecha = $reinfestacionFecha;

            $this->campania->reinfestacion_fecha_recojo_vaciado_infestadores =
                $this->reinfestacion_fecha_recojo_vaciado_infestadores !== ''
                ? $this->reinfestacion_fecha_recojo_vaciado_infestadores
                : null;

            $this->campania->reinfestacion_fecha_colocacion_malla =
                $this->reinfestacion_fecha_colocacion_malla !== ''
                ? $this->reinfestacion_fecha_colocacion_malla
                : null;

            $this->campania->reinfestacion_fecha_retiro_malla =
                $this->reinfestacion_fecha_retiro_malla !== ''
                ? $this->reinfestacion_fecha_retiro_malla
                : null;


            $this->campania->save();

            $this->alert('success', 'Evaluación de reinfestación guardada correctamente');

            $this->mostrarFormularioEvalReinfestacion = false;

            $this->dispatch('refrescarInformeCampaniaXCampo');

        } catch (Exception $e) {

            $this->alert('error', 'Error al guardar la evaluación de reinfestación: ' . $e->getMessage());
        }
    }

    public function render()
    {
        return view('livewire.evaluaciones.evaluacion-reinfestacion-form-component');
    }
}

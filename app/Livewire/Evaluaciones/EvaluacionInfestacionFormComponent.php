<?php

namespace App\Livewire\Evaluaciones;

use App\Models\CampoCampania;
use App\Models\Cuadrillero;
use App\Models\EvalBrotesPorPiso;
use App\Models\PlanEmpleado;
use App\Services\Produccion\MateriaPrima\BrotesPorPisoServicio;
use App\Services\Produccion\Planificacion\CampaniaServicio;
use App\Support\CalculoHelper;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;
use App\Models\EvaluacionBrotesXPisoDetalle;
use App\Support\ExcelHelper;
use Exception;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use App\Models\EvaluacionBrotesXPiso;
use Illuminate\Support\Str;

class EvaluacionInfestacionFormComponent extends Component
{
    use LivewireAlert;
    public $mostrarFormularioEvalInfestacion = false;
    public $infestacion_fecha;
    public $infestacion_fecha_recojo_vaciado_infestadores;
    public $infestacion_fecha_colocacion_malla;
    public $infestacion_fecha_retiro_malla;
    public $campania;
    protected $listeners = ['editarInfestacion','sincronizarInformacionInfestacion'];

    public function mount()
    {

    }
    public function sincronizarInformacionInfestacion($campaniaId)
    {
        try {

            $campaniaServicio = new CampaniaServicio();
            $campaniaServicio->registrarHistorialDeInfestaciones($campaniaId,'infestacion');

            $this->dispatch('refrescarInformeCampaniaXCampo');
            $this->alert('success', 'Datos sincronizados correctamente');
        } catch (\Throwable $th) {
            $this->alert('error', $th->getMessage());
        }
    }
    public function editarInfestacion($campaniaId)
    {
        $campania = CampoCampania::find($campaniaId);
        if (!$campania) {
            $this->alert('error', 'Campaña no encontrada');
            return;
        }
        $this->campania = $campania;
        $this->infestacion_fecha = $campania->infestacion_fecha;
        $this->infestacion_fecha_recojo_vaciado_infestadores = $campania->infestacion_fecha_recojo_vaciado_infestadores;
        $this->infestacion_fecha_colocacion_malla = $campania->infestacion_fecha_colocacion_malla;
        $this->infestacion_fecha_retiro_malla = $campania->infestacion_fecha_retiro_malla;
        $this->mostrarFormularioEvalInfestacion = true;
    }
    public function guardarEvaluacionInfestacion()
    {
        try {

            $this->campania->infestacion_fecha = $this->infestacion_fecha !== '' ? $this->infestacion_fecha : null;
            $this->campania->infestacion_fecha_recojo_vaciado_infestadores = $this->infestacion_fecha_recojo_vaciado_infestadores !== ''
                ? $this->infestacion_fecha_recojo_vaciado_infestadores
                : null;

            $this->campania->infestacion_fecha_colocacion_malla = $this->infestacion_fecha_colocacion_malla !== ''
                ? $this->infestacion_fecha_colocacion_malla
                : null;

            $this->campania->infestacion_fecha_retiro_malla = $this->infestacion_fecha_retiro_malla !== ''
                ? $this->infestacion_fecha_retiro_malla
                : null;
            $this->campania->save();

            $this->alert('success', 'Evaluación de infestación guardada correctamente');
            $this->mostrarFormularioEvalInfestacion = false;
            $this->dispatch('evaluacionInfestacionGuardada');

        } catch (Exception $e) {
            $this->alert('error', 'Error al guardar la evaluación de infestación: ' . $e->getMessage());
        }
    }

    public function render()
    {
        return view('livewire.evaluaciones.evaluacion-infestacion-form-component');
    }
}
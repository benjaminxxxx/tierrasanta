<?php

namespace App\Livewire\Evaluaciones;

use App\Models\CampoCampania;
use App\Models\Cuadrillero;
use App\Models\EvalBrotesPorPiso;
use App\Models\PlanEmpleado;
use App\Services\Produccion\MateriaPrima\BrotesPorPisoServicio;
use App\Services\Produccion\Planificacion\CampaniaServicio;
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

class EvaluacionBrotesFormComponent extends Component
{
    use LivewireAlert;
    public $mostrarFormulario = false;
    public $evaluacionBrotesXPisoId;
    public $evaluacionBrotesXPiso;
    public $idTable;
    public $campania;
    public $evaluadoresNombres = [];
    public $evaluador;
    public $campaniasDisponibles = [];
    public $fecha;
    public $detalleBrotesPorPiso = [];
    public $fileNameReporteBroteXPiso = "EVALUACION BROTE X PISO";
    public $campoSeleccionado;
    public $metros_cama_ha;
    public $campaniaSeleccionada;
    public $modoEdicion = false;
    protected $listeners = ["editarEvaluacionBrotesPorPiso", "agregarEvaluacionBrote", "storeTableDataBrotesXPiso"];
    public function mount()
    {
        $this->idTable = "table" . Str::random(15);
        $planilla = PlanEmpleado::pluck('nombres')->toArray();
        $cuadrilla = Cuadrillero::pluck('nombres')->toArray();
        $this->evaluadoresNombres = array_values(array_unique(array_merge($planilla, $cuadrilla)));

    }
    public function updatedCampoSeleccionado()
    {
        $this->cargarCampanias();
    }
    public function updatedCampaniaSeleccionada()
    {
        $this->cargarInformacionEvaluacion();
    }
    public function cargarInformacionEvaluacion()
    {
        try {
            $this->campania = CampoCampania::findOrFail($this->campaniaSeleccionada);
            $evaluacionBrotes = $this->campania->evaluacionBrotesXPiso;

            $this->evaluacionBrotesXPisoId = null;
            $this->metros_cama_ha = null;
            $this->fecha = null;
            $this->evaluador = null;
            if ($evaluacionBrotes) {
                $this->evaluacionBrotesXPisoId = $evaluacionBrotes->id;
                $this->metros_cama_ha = $evaluacionBrotes->metros_cama_ha;
                $this->fecha = $evaluacionBrotes->fecha;
                $this->evaluador = $evaluacionBrotes->evaluador;

                $this->detalleBrotesPorPiso = [];

                if ($evaluacionBrotes->detalles->count() > 0) {
                    $this->detalleBrotesPorPiso = $evaluacionBrotes->detalles->map(function ($detalle) {
                        return [
                            'numero_cama' => $detalle->numero_cama,
                            'longitud_cama' => $detalle->longitud_cama,

                            // valores base
                            'brotes_aptos_2p_actual' => $detalle->brotes_aptos_2p_actual,
                            'brotes_aptos_2p_despues_n_dias' => round($detalle->brotes_aptos_2p_despues_n_dias, 0),
                            'brotes_aptos_3p_actual' => round($detalle->brotes_aptos_3p_actual, 0),
                            'brotes_aptos_3p_despues_n_dias' => round($detalle->brotes_aptos_3p_despues_n_dias, 0),

                            // valores calculados desde los accessors
                            'brotes_2p_actual_por_mt' => round($detalle->brotes_2p_actual_por_mt, 2),
                            'brotes_2p_despues_por_mt' => round($detalle->brotes_2p_despues_por_mt, 2),
                            'brotes_3p_actual_por_mt' => round($detalle->brotes_3p_actual_por_mt, 2),
                            'brotes_3p_despues_por_mt' => round($detalle->brotes_3p_despues_por_mt, 2),
                            'total_actual_por_mt' => round($detalle->total_actual_por_mt, 2),
                            'total_despues_por_mt' => round($detalle->total_despues_por_mt, 2),
                        ];
                    })->toArray();
                }

                $this->dispatch('cargarDataBrotesXPiso', $this->detalleBrotesPorPiso);
            } else {
                $this->dispatch('cargarDataBrotesXPiso', []);
            }
        } catch (\Throwable $th) {
            $this->fecha_siembra = null;
            $this->alert('error', 'La campaña seleccionada no es válida.');
        }
    }
    public function cargarCampanias()
    {
        $this->campaniaSeleccionada = null;
        $this->campania = null;

        if (!$this->campoSeleccionado) {
            return;
        }

        $this->campaniasDisponibles = app(CampaniaServicio::class)
            ->buscarCampaniasPorCampo($this->campoSeleccionado);

        if ($this->campaniasDisponibles->count() > 0) {
            $this->campania = $this->campaniasDisponibles->first();
        }
    }
    public function editarEvaluacionBrotesPorPiso($evaluacionBrotesXPisoId)
    {
        try {
            $this->resetForm();
            $this->modoEdicion = true;
            $evaluacionBrotesXPiso = EvalBrotesPorPiso::findOrFail($evaluacionBrotesXPisoId);

            $this->campoSeleccionado = $evaluacionBrotesXPiso->campania->campo;
            $this->cargarCampanias();
            $this->campaniaSeleccionada = $evaluacionBrotesXPiso->campania->id;
            $this->cargarInformacionEvaluacion();
            $this->mostrarFormulario = true;

        } catch (\Throwable $th) {
            $this->evaluacionBrotesXPiso = null;
            $this->alert('error', $th->getMessage());
        }
    }
    public function storeTableDataBrotesXPiso($datos)
    {
        try {
            $datosGenerales = [
                'id' => $this->evaluacionBrotesXPisoId, // puede ser null
                'campo' => $this->campoSeleccionado,
                'fecha' => $this->fecha,
                'evaluador' => $this->evaluador,
                'metros_cama_ha' => $this->metros_cama_ha,
                'campania_id' => $this->campania->id,
                'detalles' => $datos
            ];
            $this->evaluacionBrotesXPisoId = app(BrotesPorPisoServicio::class)->registrar($datosGenerales);
            $this->resetErrorBag();
            $this->dispatch('brotesPorPisoRegistrado');
            $this->cargarInformacionEvaluacion();
            $this->alert('success', 'Registro exitoso de brotes por piso.');
        } catch (ValidationException $ve) {
            $this->alert('error', $ve->getMessage());
            throw $ve;
        } catch (\Throwable $th) {
            $this->alert('error', $th->getMessage());
        }
    }


    public function agregarEvaluacionBrote($campaniaId = null)
    {
        $this->resetForm();
        
        if ($campaniaId) {
            $campania = CampoCampania::find($campaniaId);
            if ($campania) {
                $this->campoSeleccionado = $campania->campo;
                $this->campania = $campania;
            }
        }

        $this->mostrarFormulario = true;
    }
    public function resetForm()
    {
        $this->modoEdicion = false;
        $this->resetErrorBag();
        $this->reset([
            'evaluador',
            'metros_cama_ha',
            'fecha',
            'evaluacionBrotesXPisoId',
            'evaluacionBrotesXPiso',
            'campoSeleccionado',
            'campaniaSeleccionada',
            'campania'
        ]);
        $this->dispatch('cargarDataBrotesXPiso', []);
        $this->detalleBrotesPorPiso = [];
        $this->fecha = Carbon::now()->format('Y-m-d');
    }
    public function render()
    {
        return view('livewire.evaluaciones.evaluacion-brotes-form-component');
    }
}

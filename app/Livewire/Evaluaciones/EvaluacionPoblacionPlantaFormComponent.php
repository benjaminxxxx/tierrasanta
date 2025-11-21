<?php

namespace App\Livewire\Evaluaciones;

use App\Models\Campo;
use App\Models\CampoCampania;
use App\Models\Cuadrillero;
use App\Models\EvalPoblacionPlanta;
use App\Models\PlanEmpleado;
use App\Models\PoblacionPlantas;
use App\Services\Produccion\MateriaPrima\PoblacionPlantaServicio;
use App\Services\Produccion\Planificacion\CampaniaServicio;
use Exception;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;
use Illuminate\Support\Str;


class EvaluacionPoblacionPlantaFormComponent extends Component
{
    use LivewireAlert;
    public $mostrarFormulario = false;
    public $poblacionPlantaId;
    public $evaluadoresNombres = [];
    public $campaniasDisponibles = [];
    public $idTable;
    public $detalleEvaluacionPoblacionPlanta = [];
    public $campoSeleccionado;
    public $campaniaSeleccionada;
    public $area_lote;
    public $fecha_siembra;
    public $evaluador;
    public $metros_cama_ha;
    public $campania;
    public $fecha_eval_cero;
    public $fecha_eval_resiembra;
    public $modoEdicion = false;
    protected $listeners = ['agregarEvaluacion', 'editarPoblacionPlanta', 'storeTableDataPoblacionPlanta'];
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
        $this->buscarArea();
    }
    public function cargarInformacionEvaluacion()
    {
        try {
            $this->campania = CampoCampania::findOrFail($this->campaniaSeleccionada);
            $this->buscarArea();
            $this->fecha_siembra = $this->campania->fecha_siembra;
            $evaluacionPoblacion = EvalPoblacionPlanta::where('campania_id', $this->campania->id)
                ->first();

            $this->poblacionPlantaId = null;
            $this->area_lote = null;
            $this->metros_cama_ha = null;
            $this->fecha_eval_cero = null;
            $this->fecha_eval_resiembra = null;
            $this->evaluador = null;
            $this->fecha_siembra = $this->campania->fecha_siembra;

            if ($evaluacionPoblacion) {
                $this->poblacionPlantaId = $evaluacionPoblacion->id;
                $this->area_lote = $evaluacionPoblacion->area_lote;
                $this->metros_cama_ha = $evaluacionPoblacion->metros_cama_ha;
                $this->fecha_eval_cero = $evaluacionPoblacion->fecha_eval_cero;
                $this->fecha_eval_resiembra = $evaluacionPoblacion->fecha_eval_resiembra;
                $this->evaluador = $evaluacionPoblacion->evaluador;

                $this->detalleEvaluacionPoblacionPlanta = [];

                if ($evaluacionPoblacion->detalles->count() > 0) {
                    $this->detalleEvaluacionPoblacionPlanta = $evaluacionPoblacion->detalles->map(function ($detalle) {
                        return [
                            'numero_cama' => $detalle->numero_cama,
                            'longitud_cama' => $detalle->longitud_cama,
                            'eval_cero_plantas_x_hilera' => $detalle->eval_cero_plantas_x_hilera,
                            'plantas_x_metro_cero' => round($detalle->plantas_por_metro_cero, 0),
                            'eval_resiembra_plantas_x_hilera' => $detalle->eval_resiembra_plantas_x_hilera,
                            'plantas_x_metro_resiembra' => round($detalle->plantas_por_metro_resiembra, 0)
                        ];
                    })->toArray();
                }
                $this->dispatch('cargarData', $this->detalleEvaluacionPoblacionPlanta);
            } else {
                $this->dispatch('cargarData', []);
            }
        } catch (\Throwable $th) {
            $this->fecha_siembra = null;
            $this->alert('error', 'La campaña seleccionada no es válida.');
        }
    }
    public function updatedCampaniaSeleccionada()
    {
        $this->cargarInformacionEvaluacion();
    }
    public function buscarArea()
    {
        $this->area_lote = null;

        if ($this->campoSeleccionado) {
            return;
        }

        $campo = Campo::find($this->campoSeleccionado);
        if ($campo) {
            $this->area_lote = $campo->area;
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
    public function storeTableDataPoblacionPlanta($datos)
    {
        try {
            $datosGenerales = [
                'id' => $this->poblacionPlantaId, // puede ser null
                'area_lote' => $this->area_lote,
                'fecha_siembra' => $this->fecha_siembra,
                'metros_cama_ha' => $this->metros_cama_ha,
                'evaluador' => $this->evaluador,
                'fecha_eval_cero' => $this->fecha_eval_cero,
                'fecha_eval_resiembra' => $this->fecha_eval_resiembra,
                'campania_id' => $this->campania->id,
                'detalles' => $datos
            ];

            $this->poblacionPlantaId = app(PoblacionPlantaServicio::class)->registrar($datosGenerales);
            $this->resetErrorBag();
            $this->dispatch('poblacionPlantasRegistrado');
            $this->alert('success', 'Registro exitoso de población de plantas.');
        } catch (ValidationException $ve) {
            $this->alert('error', $ve->getMessage());
            throw $ve;
        } catch (\Throwable $th) {
            $this->alert('error', $th->getMessage());
        }
    }

    public function editarPoblacionPlanta($poblacionId)
    {
        try {
            $this->modoEdicion = true;
            $this->poblacionPlantaId = null;
            $evaluacion = EvalPoblacionPlanta::findOrFail($poblacionId);
            if (!$evaluacion) {
                throw new Exception('La evaluación de población de planta no existe.');
            }

            $this->poblacionPlantaId = $poblacionId;
            $this->campoSeleccionado = $evaluacion->campania->campo;
            $this->cargarCampanias();
            $this->campaniaSeleccionada = $evaluacion->campania_id;
            $this->cargarInformacionEvaluacion();
            $this->mostrarFormulario = true;

        } catch (\Throwable $th) {
            $this->alert('error', $th->getMessage());
        }
    }

    public function resetearCampos()
    {
        $this->reset([
            'poblacionPlantaId',
            'area_lote',
            'metros_cama_ha',
            'fecha_siembra',
            'evaluador',
            'fecha_eval_cero',
            'fecha_eval_resiembra',
            'campaniaSeleccionada',
            'campaniasDisponibles',
            'campoSeleccionado'
        ]);
        $this->resetErrorBag();
        $this->fecha = Carbon::now()->format('Y-m-d');
        $this->dispatch('cargarData', $this->detalleEvaluacionPoblacionPlanta);
    }
    public function agregarEvaluacion($campaniaId = null)
    {
        try {
            $this->modoEdicion = false;
            $this->resetearCampos();

            if ($campaniaId) {
                $campania = CampoCampania::find($campaniaId);
                if ($campania) {
                    $this->campoSeleccionado = $campania->campo;
                    $this->cargarCampanias();
                    $this->campania = $campania;
                    $this->campaniaSeleccionada = $campania->id;
                    $this->cargarInformacionEvaluacion();
                }
            }
            $this->dispatch('cargarData', []);
            $this->mostrarFormulario = true;
        } catch (\Throwable $th) {
            return $this->alert('error', $th->getMessage());
        }
    }

    public function render()
    {
        return view('livewire.evaluaciones.evaluacion-poblacion-planta-form-component');
    }
}

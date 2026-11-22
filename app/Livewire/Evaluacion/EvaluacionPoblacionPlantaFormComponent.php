<?php

namespace App\Livewire\Evaluacion;

use App\Constants\Permisos;
use App\Models\Campo;
use App\Models\CampoCampania;
use App\Models\Cuadrillero;
use App\Models\EvalPoblacionPlanta;
use App\Models\PlanEmpleado;
use App\Services\Evaluacion\PoblacionPlantaServicio;
use App\Services\Campania\CampaniaServicio;
use Exception;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;
use Illuminate\Support\Str;


class EvaluacionPoblacionPlantaFormComponent extends Component
{
    use LivewireAlert, \App\Traits\HandlesAlerts, \App\Traits\ConSiembraEvaluacion;
    public $mostrarFormulario = false;
    public $poblacionPlantaId;
    public $evaluadoresNombres = [];
    public $evaluador;
    public $campaniasDisponibles = [];
    public $idTable;
    public $detalleEvaluacionPoblacionPlanta = [];
    public $campoSeleccionado;
    public $campaniaSeleccionada;
    public $area_lote;
    public $fecha_siembra;
    public $metros_cama_ha;
    public $campania;
    public $fecha_eval_cero;
    public $fecha_eval_resiembra;
    public $modoEdicion = false;
    protected $listeners = ['agregarEvaluacion', 'editarPoblacionPlanta'];
    public function mount()
    {
        $this->idTable = "table" . Str::random(15);
        $planilla = PlanEmpleado::get()->pluck('nombres')->toArray();
        $cuadrilla = Cuadrillero::pluck('nombres')->toArray();
        $this->evaluadoresNombres = array_values(array_unique(array_merge($planilla, $cuadrilla)));

    }
    public function updatedCampoSeleccionado()
    {
        $this->cargarCampanias();
        $this->buscarArea();
    }
    public function cargarInformacionEvaluacion(?int $siembraElegida = null)
    {
        try {
            $this->campania = CampoCampania::findOrFail($this->campaniaSeleccionada);
            $this->cargarSiembras($this->campania, $siembraElegida);
            $this->cargarEvaluacionDeSiembra();
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException) {
            $this->fecha_siembra = null;
            $this->errorAlert('La campaña seleccionada no es válida.');
        } catch (\Throwable $th) {
            // Otro error: se muestra tal cual (y queda ticket), no se disfraza de "campaña no válida"
            $this->fecha_siembra = null;
            $this->errorAlert($th);
        }
    }

    /** Lo de la siembra elegida: una evaluación por siembra, con su área y su fecha de siembra. */
    private function cargarEvaluacionDeSiembra(): void
    {
        $siembra = $this->siembraActual();
        $this->poblacionPlantaId = null;
        $this->metros_cama_ha = null;
        $this->fecha_eval_cero = null;
        $this->fecha_eval_resiembra = null;
        $this->evaluador = null;
        $this->detalleEvaluacionPoblacionPlanta = [];
        $this->area_lote = $siembra['area'] ?? null;
        // Sin plantación registrada la fecha de siembra queda vacía (no es un error de la campaña)
        $this->fecha_siembra = $siembra['fecha_siembra'] ?? $this->campania?->fecha_siembra?->format('Y-m-d');

        $evaluacionPoblacion = $siembra ? EvalPoblacionPlanta::with('detalles')->where('campania_sector_id', $siembra['id'])->first() : null;
        if ($evaluacionPoblacion) {
            $this->poblacionPlantaId = $evaluacionPoblacion->id;
            $this->area_lote = (!is_null($evaluacionPoblacion->area_lote)) ? $evaluacionPoblacion->area_lote : $this->area_lote;
            $this->metros_cama_ha = $evaluacionPoblacion->metros_cama_ha;
            $this->fecha_eval_cero = $evaluacionPoblacion->fecha_eval_cero;
            $this->fecha_eval_resiembra = $evaluacionPoblacion->fecha_eval_resiembra;
            $this->evaluador = $evaluacionPoblacion->evaluador;

            $this->detalleEvaluacionPoblacionPlanta = $evaluacionPoblacion->detalles->map(fn($detalle) => [
                //'numero_cama' => $detalle->numero_cama, ya no es visible
                'longitud_cama' => $detalle->longitud_cama,

                // PLANTAS
                'eval_cero_plantas_x_hilera' => $detalle->eval_cero_plantas_x_hilera,
                'plantas_x_metro_cero' => round($detalle->plantas_por_metro_cero, 0),

                'eval_resiembra_plantas_x_hilera' => $detalle->eval_resiembra_plantas_x_hilera,
                'plantas_x_metro_resiembra' => round($detalle->plantas_por_metro_resiembra, 0),

                // BRAZOS 2° PISO
                'brazos2_piso_x_hilera_cero' => $detalle->brazos2_piso_x_hilera_cero,
                'brazos2_piso_x_metro_cero' => round($detalle->brazos2_piso_x_metro_cero, 0),

                // BRAZOS 3° PISO
                'brazos3_piso_x_hilera_cero' => $detalle->brazos3_piso_x_hilera_cero,
                'brazos3_piso_x_metro_cero' => round($detalle->brazos3_piso_x_metro_cero, 0),
            ])->toArray();
        }
        $this->cargarTabla($this->detalleEvaluacionPoblacionPlanta);
    }

    private function cargarTabla(array $filas): void
    {
        $this->dispatch('cargarData', $filas, $this->soloLectura());
    }

    private function soloLectura(): bool
    {
        $usuario = auth()->user();
        return $this->campaniaCerrada || !$this->siembraId
            || !$usuario?->can($this->poblacionPlantaId ? Permisos::PLANTA_EVALUACION_EDITAR : Permisos::PLANTA_EVALUACION_CREAR);
    }

    protected function alCambiarSiembra(): void
    {
        $this->cargarEvaluacionDeSiembra();
    }

    protected function campaniaDeSiembras(): ?CampoCampania
    {
        return $this->campaniaSeleccionada ? CampoCampania::find($this->campaniaSeleccionada) : null;
    }

    protected function puedeAgregarSiembra(): bool
    {
        return (bool) auth()->user()?->can(Permisos::PLANTA_EVALUACION_CREAR);
    }

    protected function puedeCorregirSiembra(): bool
    {
        return (bool) auth()->user()?->can(Permisos::PLANTA_EVALUACION_EDITAR);
    }

    public function updatedCampaniaSeleccionada()
    {
        if (!$this->campaniaSeleccionada) {
            $this->campania = null;
            $this->limpiarSiembras();
            $this->cargarTabla([]);
            return;
        }
        $this->cargarInformacionEvaluacion();
    }
    public function buscarArea()
    {
        $this->area_lote = null;

        if (empty($this->campoSeleccionado)) {
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
        $this->limpiarSiembras();

        if (!$this->campoSeleccionado) {
            return;
        }
        $this->campaniasDisponibles = app(CampaniaServicio::class)
            ->buscarCampaniasPorCampo($this->campoSeleccionado);

    }
    public function storeTableDataPoblacionPlanta($datos)
    {
        try {
            abort_unless(auth()->user()?->can($this->poblacionPlantaId ? Permisos::PLANTA_EVALUACION_EDITAR : Permisos::PLANTA_EVALUACION_CREAR), 403);
            if (!$this->campania) {
                throw new \App\Exceptions\ErrorControlado('Elige la campaña.');
            }
            if (!$this->siembraId) {
                throw new \App\Exceptions\ErrorControlado('Elige primero qué siembra estás evaluando.');
            }
            $datosGenerales = [
                'id' => $this->poblacionPlantaId, // puede ser null
                'area_lote' => $this->area_lote,
                'fecha_siembra' => $this->fecha_siembra,
                'metros_cama_ha' => $this->metros_cama_ha,
                'evaluador' => $this->evaluador,
                'fecha_eval_cero' => $this->fecha_eval_cero,
                'fecha_eval_resiembra' => $this->fecha_eval_resiembra,
                'campania_id' => $this->campania->id,
                'campania_sector_id' => $this->siembraId,
                'detalles' => $datos
            ];

            $this->poblacionPlantaId = app(PoblacionPlantaServicio::class)->registrar($datosGenerales);
            $this->cargarSiembras($this->campania, $this->siembraId);
            $this->resetErrorBag();
            $this->dispatch('poblacionPlantasRegistrado');
            $this->alert('success', 'Registro exitoso de población de plantas.');
        } catch (ValidationException $ve) {
            throw $ve;
        } catch (\Throwable $th) {
            $this->errorAlert($th);
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

            $this->campoSeleccionado = $evaluacion->campania->campo;
            $this->cargarCampanias();
            $this->campaniaSeleccionada = $evaluacion->campania_id;
            // Vincula primero las antiguas sin siembra, para abrir justo la siembra de esta evaluación
            app(\App\Services\Evaluacion\Siembra\EvaluacionSiembraProceso::class)->vincularCampania($evaluacion->campania);
            $this->cargarInformacionEvaluacion((int) $evaluacion->fresh()->campania_sector_id);
            $this->mostrarFormulario = true;

        } catch (\Throwable $th) {
            $this->errorAlert($th);
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
            'campoSeleccionado',
            'campania',
            'detalleEvaluacionPoblacionPlanta',
        ]);
        $this->limpiarSiembras();
        $this->resetErrorBag();
        $this->cargarTabla([]);
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
            $this->mostrarFormulario = true;
        } catch (\Throwable $th) {
            return $this->errorAlert($th);
        }
    }

    public function render()
    {
        return view('livewire.evaluacion.evaluacion-poblacion-planta-form-component', [
            'eval' => $this->poblacionPlantaId ? EvalPoblacionPlanta::with('detalles')->find($this->poblacionPlantaId) : null,
            'puedeGuardar' => !$this->soloLectura(),
            'puedeAgregarSiembra' => !$this->campaniaCerrada && $this->puedeAgregarSiembra(),
            'puedeCorregirSiembra' => !$this->campaniaCerrada && $this->puedeCorregirSiembra(),
        ]);
    }
}

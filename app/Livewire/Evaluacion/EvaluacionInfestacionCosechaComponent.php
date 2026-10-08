<?php

namespace App\Livewire\Evaluacion;

use App\Models\CampoCampania;
use App\Services\Campania\Etapa\CampaniaEtapaReglas;
use App\Services\Campania\Etapa\CampaniaEvaluacionInfestacionConsulta;
use App\Services\Evaluacion\EvaluacionInfestacionPencaServicio;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;

class EvaluacionInfestacionCosechaComponent extends Component
{
    use LivewireAlert;
    public $campoSeleccionado;
    public $campaniaSeleccionada;
    public $campaniasPorCampo = [];
    public $table = [];
    public $fechas = [];
    public $campania;
    public $fechaEvaluacion;
    public $idTable;
    public $proyeccionCochinillaXGramo;
    public $ultimaInfestacion;
    public $primeraEvalFecha;
    public $segundaEvalFecha;
    public $terceraEvalFecha;
    protected $listeners = ['confirmarEliminarEvaluacionInfestacion'];
    public function mount($campaniaId = null)
    {
        // Desde tareas pendientes llega ?campania=ID
        $campaniaId ??= request()->query('campania');
        if ($campaniaId) {
            $this->campania = CampoCampania::find($campaniaId);
            if ($this->campania) {
                $this->campoSeleccionado = $this->campania->campo;
                $this->campaniaSeleccionada = $this->campania->id;
                $this->campaniasPorCampo = $this->listarCampanias($this->campoSeleccionado);
                $this->buscarUltimaInfestacion();
                $this->table = app(EvaluacionInfestacionPencaServicio::class)->generar($this->campania);
            }
        }
        $this->fechaEvaluacion = Carbon::now()->format('Y-m-d');
        $this->idTable = 'table_' . Str::random(10);
    }


    public function renderizarTabla()
    {
        $tabla = $this->campania ? app(EvaluacionInfestacionPencaServicio::class)->generar($this->campania) : [];
        $this->table = $tabla;

        $this->dispatch('recargarEvaluacion', [
            'table' => $tabla,
            'encabezados' => $this->encabezados(),
        ]);
    }


    public function updatedCampaniaSeleccionada($valor)
    {
        $this->campania = CampoCampania::find($valor);
        $this->buscarUltimaInfestacion();
        $this->renderizarTabla();
    }
    public function updatedCampoSeleccionado($valor)
    {
        $this->campaniasPorCampo = $this->listarCampanias($valor);
        $this->campaniaSeleccionada = null;
        $this->campania = null;
        $this->buscarUltimaInfestacion();
        $this->renderizarTabla();
    }
    /** La más reciente primero. */
    private function listarCampanias($campo)
    {
        return $campo ? CampoCampania::where('campo', $campo)->orderByDesc('fecha_inicio')->get() : collect();
    }

    /** Evaluaciones configuradas (días después de la infestación) con su fecha sugerida y si ya están registradas. */
    private function calendario(): array
    {
        if (!$this->campania) {
            return array_map(fn($d, $k) => ['numero' => $k + 1, 'dias' => $d, 'fecha' => null, 'registrable' => $k < CampaniaEtapaReglas::EVALUACIONES_INFESTACION_REGISTRABLES, 'registrada' => false],
                CampaniaEtapaReglas::diasEvaluacionInfestacion(), array_keys(CampaniaEtapaReglas::diasEvaluacionInfestacion()));
        }
        $base = $this->ultimaInfestacion ? Carbon::parse($this->ultimaInfestacion->fecha) : null;
        return CampaniaEvaluacionInfestacionConsulta::calendario($this->campania, $base);
    }

    /** Encabezado de cada una de las tres columnas de la tabla: "Evaluación 1 · 60 días · 12/08". */
    private function encabezados(): array
    {
        $calendario = $this->calendario();
        return array_map(function ($n) use ($calendario) {
            $e = $calendario[$n - 1] ?? null;
            $extra = $e ? $e['dias'] . ' días' . ($e['fecha'] ? ' · ' . $e['fecha']->format('d/m/Y') : '') : 'sin día configurado';
            return "Evaluación {$n}<br><small>{$extra}</small>";
        }, [1, 2, 3]);
    }

    public function buscarUltimaInfestacion()
    {
        $this->reset(['primeraEvalFecha', 'segundaEvalFecha', 'terceraEvalFecha', 'ultimaInfestacion', 'proyeccionCochinillaXGramo']);

        if ($this->campania) {
            $this->ultimaInfestacion = CampaniaEvaluacionInfestacionConsulta::infestacionBase($this->campania);
            $this->primeraEvalFecha = $this->campania->eval_infest_fecha_primera;
            $this->segundaEvalFecha = $this->campania->eval_infest_fecha_segunda;
            $this->terceraEvalFecha = $this->campania->eval_infest_fecha_tercera;
            $this->proyeccionCochinillaXGramo = $this->campania->eval_cosch_proj_coch_x_gramo;

        }
    }
    public function guardarDatosEvaluacionInfestacionCosecha(array $datos)
    {
        try {
            if (!$this->campania) {
                return;
            }
            $this->campania->eval_infest_fecha_primera = $this->primeraEvalFecha;
            $this->campania->eval_infest_fecha_segunda = $this->segundaEvalFecha;
            $this->campania->eval_infest_fecha_tercera = $this->terceraEvalFecha;
            $this->campania->eval_cosch_proj_coch_x_gramo = $this->proyeccionCochinillaXGramo;
            $this->campania->save();
           
            app(EvaluacionInfestacionPencaServicio::class)->guardar($this->campania, $datos);
            $this->renderizarTabla();
            $this->alert('success', 'Evaluación guardada correctamente.');
        } catch (\Exception $e) {
            $this->alert('error', $e->getMessage());
        }
    }


    public function render()
    {
        $dias = CampaniaEtapaReglas::diasEvaluacionInfestacion();
        return view('livewire.evaluacion.evaluacion-infestacion-cosecha-component', [
            'calendario' => $this->calendario(),
            'textoDias' => CampaniaEtapaReglas::textoDias($dias),
            'encabezados' => $this->encabezados(),
        ]);
    }
}

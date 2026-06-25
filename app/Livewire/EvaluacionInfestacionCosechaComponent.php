<?php

namespace App\Livewire;

use App\Models\CampoCampania;
use App\Models\Configuracion;
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
        //$configuraciones = Configuracion::get()->pluck('valor', 'codigo')->toArray();
        if ($campaniaId) {
            $this->campania = CampoCampania::find($campaniaId);
            if ($this->campania) {
                $this->campoSeleccionado = $this->campania->campo;
                $this->campaniaSeleccionada = $this->campania->id;
                $this->proyeccionCochinillaXGramo = $this->campania->eval_cosch_proj_coch_x_gramo;
                $this->renderizarTabla();
            }
        }
        $this->fechaEvaluacion = Carbon::now()->format('Y-m-d');
        $this->idTable = 'table_' . Str::random(10);
    }


    public function renderizarTabla()
    {
        if (!$this->campania) {
            $this->dispatch('recargarEvaluacion', [
                'table' => []
            ]);
            return;
        }

        $tabla = app(EvaluacionInfestacionPencaServicio::class)->generar($this->campania);

        $this->table = $tabla;

        $this->dispatch('recargarEvaluacion', [
            'table' => $tabla,
        ]);
    }


    public function updatedCampaniaSeleccionada($valor)
    {
        $this->campania = CampoCampania::find($valor);
        $this->renderizarTabla();
        $this->buscarUltimaInfestacion();
    }
    public function updatedCampoSeleccionado($valor)
    {
        $this->campaniasPorCampo = CampoCampania::where('campo', $valor)->get();
        $this->campaniaSeleccionada = null;
        $this->campania = null;
        $this->renderizarTabla();
        $this->buscarUltimaInfestacion();
    }
    public function buscarUltimaInfestacion()
    {
        $this->reset(['primeraEvalFecha', 'segundaEvalFecha', 'terceraEvalFecha', 'ultimaInfestacion', 'proyeccionCochinillaXGramo']);

        if ($this->campania) {
            $this->ultimaInfestacion = $this->campania->infestaciones()->latest('fecha')->first();
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
        return view('livewire.evaluacion-infestacion-cosecha-component');
    }
}

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
        $this->reset(['primeraEvalFecha','segundaEvalFecha','terceraEvalFecha','ultimaInfestacion','proyeccionCochinillaXGramo']);

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
    }

    /*
    public function guardarDatosEvaluacionInfestacionCosecha($datos)
    {
        dd($datos);
        array:14 [▼ // app\Livewire\EvaluacionInfestacionCosechaComponent.php:156
  0 => array:7 [▼
    "n_pencas" => 1
    "eval_primera_piso_2" => 12
    "eval_primera_piso_3" => 13
    "eval_segunda_piso_2" => 14
    "eval_segunda_piso_3" => 14
    "eval_tercera_piso_2" => 141
    "eval_tercera_piso_3" => 15
  ]
  1 => array:7 [▶]
  2 => array:7 [▶]
  3 => array:7 [▶]

        if (!$this->campania) {
            return;
        }
        $evaluaciones = $this->campania->evaluacionInfestaciones()->orderBy('fecha')->get();
        $fechas = $evaluaciones->pluck('fecha')->unique()->values();

        $fechaMap = [];
        foreach ($fechas as $i => $fecha) {
            $key = 'fecha' . ($i + 1);
            $fechaMap[$key] = Carbon::parse($fecha)->format('Y-m-d');
        }
        foreach ($fechaMap as $fechaKey => $fechaReal) {
            // Obtener o crear evaluación por fecha y campaña
            $evaluacion = EvaluacionInfestacion::firstOrCreate([
                'campo_campania_id' => $this->campania->id,
                'fecha' => $fechaReal,
            ]);

            // Eliminar detalles existentes por si hay datos previos
            $evaluacion->detalles()->delete();

            foreach ($datos as $fila) {
                $n = $fila['n_pencas'];

                // Guardar solo si hay algún dato
                if (isset($fila["{$fechaKey}_piso2"]) || isset($fila["{$fechaKey}_piso3"])) {
                    $evaluacion->detalles()->create([
                        'numero_penca' => $n,
                        'piso_2' => $fila["{$fechaKey}_piso2"] ?? null,
                        'piso_3' => $fila["{$fechaKey}_piso3"] ?? null,
                    ]);
                }
            }
        }
        $promedioIndividuosMitadDias = $this->campania->promedio_individuos_mitad_dias;
        $proyeccionCochinillaXGramo = $this->proyeccion_cochinilla_x_gramo;
        $numeroPencasInfestadas = $this->campania->total_hectarea_brotes;

        $proyeccionGramosCochinillaXPenca = null;
        $proyeccionRendimientoHa = null;

        // Validar que ambos valores no sean null y que el divisor no sea 0
        if (!is_null($promedioIndividuosMitadDias) && !is_null($proyeccionCochinillaXGramo) && $proyeccionCochinillaXGramo != 0) {
            $proyeccionGramosCochinillaXPenca = $promedioIndividuosMitadDias / $proyeccionCochinillaXGramo;

            // Validar que número de pencas también sea numérico y mayor que 0
            if (!is_null($numeroPencasInfestadas) && is_numeric($numeroPencasInfestadas) && $numeroPencasInfestadas > 0) {
                $proyeccionRendimientoHa = ($proyeccionGramosCochinillaXPenca * $numeroPencasInfestadas) / 1000;
            }
        }

        $data = [
            'eval_cosch_conteo_individuos' => null,
            'eval_cosch_proj_2' => null,
            'eval_cosch_proj_coch_x_gramo' => $proyeccionCochinillaXGramo,
            'eval_cosch_proj_gramos_x_penca' => $proyeccionGramosCochinillaXPenca,
            'eval_cosch_proj_penca_inf' => $numeroPencasInfestadas,
            'eval_cosch_proj_rdto_ha' => $proyeccionRendimientoHa,
        ];

        $this->campania->update($data);


        $this->renderizarTabla();
        $this->alert('success', 'Evaluación guardada correctamente.');

    }*/
    public function render()
    {
        return view('livewire.evaluacion-infestacion-cosecha-component');
    }
}

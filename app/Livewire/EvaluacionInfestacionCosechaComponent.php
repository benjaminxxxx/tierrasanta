<?php

namespace App\Livewire;

use App\Models\CampoCampania;
use App\Models\Configuracion;
use App\Models\EvaluacionInfestacion;
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
    public $fechaExiste = false;
    public $campaniaUnica = false;
    public $proyeccion_cochinilla_x_gramo;
    protected $listeners = ['storeTableDataEvaluacionInfestacion', 'confirmarEliminarEvaluacionInfestacion'];
    public function mount($campaniaId = null, $campaniaUnica = false)
    {
        $configuraciones = Configuracion::get()->pluck('valor', 'codigo')->toArray();
        $this->proyeccion_cochinilla_x_gramo = $configuraciones['equivalencia_cochinillas_x_gramo'] ?? 1;
        if ($campaniaId) {
            $this->campania = CampoCampania::find($campaniaId);
            if ($this->campania) {
                $this->campoSeleccionado = $this->campania->campo;
                $this->campaniaSeleccionada = $this->campania->id;
                $this->renderizarTabla();
                if($this->campania->eval_cosch_proj_coch_x_gramo){
                    $this->proyeccion_cochinilla_x_gramo = $this->campania->eval_cosch_proj_coch_x_gramo;
                }
            }
        }
        $this->campaniaUnica = $campaniaUnica;
        $this->fechaEvaluacion = Carbon::now()->format('Y-m-d');
        $this->idTable = 'table_' . Str::random(10);
        $this->revisarFechaExiste();
    }
    public function crearEvaluacion()
    {
        if (!$this->campania) {
            return $this->alert('error', 'Debe seleccionar una campaña');
        }
        if (!$this->fechaEvaluacion) {
            return $this->alert('error', 'Debe seleccionar una fecha');
        }
        try {
            $revisarExistente = EvaluacionInfestacion::whereDate('fecha', $this->fechaEvaluacion)
                ->where('campo_campania_id', $this->campania->id)
                ->exists();
            if ($revisarExistente) {
                return $this->alert('warning', 'La evaluación ya existe');
            }
            EvaluacionInfestacion::create([
                'fecha' => $this->fechaEvaluacion,
                'campo_campania_id' => $this->campania->id
            ]);

            $this->revisarFechaExiste();
            $this->renderizarTabla();
            $this->alert('success', 'Evaluación creada con éxito');
        } catch (\Throwable $th) {
            $this->alert('error', $th->getMessage());
        }

    }
    public function eliminarFecha()
    {
        $evaluacion = EvaluacionInfestacion::withCount('detalles')
            ->whereDate('fecha', $this->fechaEvaluacion)
            ->where('campo_campania_id', $this->campania->id)
            ->first();

        if (!$evaluacion) {
            $this->alert('error', 'No se encontró la evaluación para eliminar.');
            return;
        }

        if ($evaluacion->detalles_count > 0) {
            $this->confirm('¿Está seguro(a) que desea eliminar el registro? Ya existen detalles registrados.', [
                'onConfirmed' => 'confirmarEliminarEvaluacionInfestacion',
                'data' => [
                    'evaluacion_id' => $evaluacion->id,
                ],
            ]);
        } else {
            $evaluacion->delete();
            $this->revisarFechaExiste();
            $this->renderizarTabla(); // Recargar si es necesario
            $this->alert('success', 'Evaluación eliminada correctamente.');
        }
    }

    public function confirmarEliminarEvaluacionInfestacion($data)
    {
        $evaluacion = EvaluacionInfestacion::find($data['evaluacion_id']);

        if ($evaluacion) {
            $evaluacion->delete();
            $this->alert('success', 'Evaluación eliminada correctamente.');
            $this->revisarFechaExiste();
            $this->renderizarTabla(); // Recargar si es necesario
        } else {
            $this->alert('error', 'La evaluación ya no existe.');
        }
    }
    public function revisarFechaExiste()
    {
        if (!$this->campania) {
            return;
        }
        $this->fechaExiste = EvaluacionInfestacion::whereDate('fecha', $this->fechaEvaluacion)
            ->where('campo_campania_id', $this->campania->id)
            ->exists();
    }
    public function updatedFechaEvaluacion()
    {
        $this->revisarFechaExiste();
    }
    public function updatedCampoSeleccionado($valor)
    {
        $this->campaniasPorCampo = CampoCampania::where('campo', $valor)->get();
        $this->campaniaSeleccionada = null;
        $this->campania = null;
        $this->renderizarTabla();
    }
    public function renderizarTabla()
    {
        if (!$this->campania) {
            $this->dispatch('recargarEvaluacion', [
                'table' => [],
                'fechas' => []
            ]);
            return;
        }

        $evaluaciones = $this->campania->evaluacionInfestaciones()->with('detalles')->orderBy('fecha')->get();
        $fechas = $evaluaciones->pluck('fecha')->unique()->values();

        $mapa = [];
        for ($i = 1; $i <= 20; $i++) {
            $mapa[$i] = ['n_pencas' => $i];
        }

        foreach ($evaluaciones as $index => $evaluacion) {
            $fechaKey = 'fecha' . ($index + 1);

            foreach ($evaluacion->detalles as $detalle) {
                $n = $detalle->numero_penca;
                if (!isset($mapa[$n]))
                    continue;

                $mapa[$n]["{$fechaKey}_piso2"] = $detalle->piso_2;
                $mapa[$n]["{$fechaKey}_piso3"] = $detalle->piso_3;
            }

            for ($i = 1; $i <= 20; $i++) {
                if (!array_key_exists("{$fechaKey}_piso2", $mapa[$i])) {
                    $mapa[$i]["{$fechaKey}_piso2"] = null;
                }
                if (!array_key_exists("{$fechaKey}_piso3", $mapa[$i])) {
                    $mapa[$i]["{$fechaKey}_piso3"] = null;
                }
            }
        }

        $tabla = array_values($mapa);

        // Fecha de infestación más reciente (puede ser null)
        $fechaInfestacion = optional($this->campania->infestaciones)->max('fecha');

        $fechasFormateadas = $fechas->map(function ($fecha, $index) use ($tabla, $fechaInfestacion) {
            $key = 'fecha' . ($index + 1);

            $suma = 0;
            $contador = 0;

            foreach ($tabla as $fila) {
                $p2 = $fila["{$key}_piso2"] ?? null;
                $p3 = $fila["{$key}_piso3"] ?? null;

                if (is_numeric($p2)) {
                    $suma += $p2;
                    $contador++;
                }
                if (is_numeric($p3)) {
                    $suma += $p3;
                    $contador++;
                }
            }

            $promedio = $contador > 0 ? round($suma / $contador, 2) : null;

            $dias = $fechaInfestacion
                ? Carbon::parse($fechaInfestacion)->diffInDays(Carbon::parse($fecha))
                : '-';

            return [
                'fecha' => Carbon::parse($fecha)->format('d/m/Y'),
                'promedio' => $promedio,
                'footer' => "N° DE INDIVIDUOS A LOS {$dias} DÍAS"
            ];
        })->toArray();

        $this->table = $tabla;
        $this->fechas = $fechasFormateadas;

        $this->dispatch('recargarEvaluacion', [
            'table' => $tabla,
            'fechas' => $fechasFormateadas,
            'fechaInfestacion' => optional($fechaInfestacion)->format('d/m/Y'),
        ]);
    }

    public function updatedCampaniaSeleccionada($valor)
    {
        $this->campania = CampoCampania::find($valor);
        $this->renderizarTabla();
        $this->revisarFechaExiste();
    }
    public function storeTableDataEvaluacionInfestacion($datos)
    {
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

    }
    public function render()
    {
        return view('livewire.evaluacion-infestacion-cosecha-component');
    }
}

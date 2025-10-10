<?php

namespace App\Livewire;

use App\Models\Campo;
use App\Models\CampoCampania;
use App\Models\PlanEmpleado;
use App\Models\PoblacionPlantas;
use App\Models\PoblacionPlantasDetalle;
use App\Services\CampaniaServicio;
use App\Services\PoblacionPlantaServicio;
use Exception;
use Illuminate\Support\Carbon;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;
use Illuminate\Support\Str;


class ReporteCampoPoblacionPlantaFormComponent extends Component
{
    use LivewireAlert;
    public $mostrarFormulario = false;
    public $poblacionPlantaId;
    public $evaluadorSeleccionado;
    public $area_lote, $metros_cama, $evaluador, $empleado_id, $fecha, $tipo_evaluacion;
    public $evaluadores = [];
    public $campania;
    public $idTable;
    public $listaCamasMuestreadas = [];
    public $promedioPlantasXCama, $promedioPlantasXMetro, $promedioPlantasHA;
    public $campoSeleccionado;
    public $campaniaUnica;
    protected $listeners = ['agregarEvaluacion', 'editarPoblacionPlanta', 'storeTableDataPoblacionPlanta'];
    public function mount($campaniaUnica = false)
    {
        $this->campaniaUnica = $campaniaUnica;
        $this->idTable = "table" . Str::random(15);
    }
    public function updatedCampoSeleccionado()
    {
        $this->buscarCampania();
        $this->buscarArea();
    }
    public function buscarArea()
    {
        if ($this->campoSeleccionado) {
            $campo = Campo::find($this->campoSeleccionado);
            if ($campo) {
                $this->area_lote = $campo->area;
            } else {
                $this->area_lote = null;
            }
        } else {
            $this->area_lote = null;
        }
    }
    public function updatedFecha()
    {
        $this->buscarCampania();
    }
    public function buscarCampania()
    {
        if ($this->campoSeleccionado && $this->fecha) {
            $this->campania = CampoCampania::masProximaAntesDe($this->fecha, $this->campoSeleccionado);
        } else {
            $this->campania = null;
        }
    }
    public function storeTableDataPoblacionPlanta($datos)
    {
        $this->validate([
            'area_lote' => 'required|numeric|min:0',
            'metros_cama' => 'required|numeric|min:0|max:99999.999',
            'evaluadorSeleccionado.nombre' => 'required|string',
            'evaluadorSeleccionado.id' => 'required|integer|exists:empleados,id',
            'fecha' => 'required|date',
            'tipo_evaluacion' => 'required',
            'campoSeleccionado' => 'required'
        ], [

            'area_lote.required' => 'El área del lote es obligatoria.',
            'area_lote.numeric' => 'El área del lote debe ser un número.',
            'metros_cama.required' => 'Los metros de cama son obligatorios.',
            'metros_cama.numeric' => 'Los metros de cama deben ser un número.',
            'metros_cama.max' => 'El número es demasiado grande, maximo 5 digitos y 3 decimales.',
            'evaluadorSeleccionado.nombre.required' => 'Debe seleccionar un evaluador.',
            'evaluadorSeleccionado.id.required' => 'Debe proporcionar un ID de evaluador.',
            'evaluadorSeleccionado.id.exists' => 'El evaluador seleccionado no es válido.',
            'fecha.required' => 'La fecha es obligatoria.',
            'fecha.date' => 'La fecha debe ser una fecha válida.',
            'tipo_evaluacion.required' => 'El tipo de evaluación es obligatorio.',
            'campoSeleccionado.required' => 'Debe seleccionar un campo.',
        ]);

        try {
            $datosGenerales = [
                'id' => $this->poblacionPlantaId, // puede ser null
                'area_lote' => $this->area_lote,
                'metros_cama' => $this->metros_cama,
                'evaluador' => $this->evaluadorSeleccionado['nombre'],
                'empleado_id' => $this->evaluadorSeleccionado['id'],
                'fecha' => $this->fecha,
                'campania_id' => $this->campania->id,
                'tipo_evaluacion' => $this->tipo_evaluacion,
            ];

            $poblacion = PoblacionPlantaServicio::registrar($datosGenerales, $datos);

            $this->poblacionPlantaId = $poblacion->id;

            $this->asignarPromedios();
            $this->alert('success', 'Registro exitoso de población de plantas.');
            $this->enviarHistorialPoblacionPlantas($this->campania->id);
            $this->dispatch('poblacionPlantasRegistrado');
        } catch (\Throwable $th) {
            $this->dispatch('log', $th->getMessage());
            $this->alert('error', 'Error: ' . $th->getMessage());
        }
    }
    /*
    public function storeTableDataPoblacionPlanta($datos)
    {
        dd($datos);
        /*
        array:4 [▼ // app\Livewire\ReporteCampoPoblacionPlantaFormComponent.php:66
  0 => array:4 [▼
    "plantas_x_cama" => 984
    "longitud_cama" => 124
    "cama_muestreada" => 1
    "plantas_x_metro" => 8
  ]
        // Validación de los datos de entrada
        $this->validate([
            'area_lote' => 'required|numeric|min:0',
            'metros_cama' => 'required|numeric|min:0|max:99999.999',
            'evaluadorSeleccionado.nombre' => 'required|string',
            'evaluadorSeleccionado.id' => 'required|integer|exists:empleados,id',
            'fecha' => 'required|date',
            'tipo_evaluacion' => 'required',
            'campoSeleccionado'=>'required'
        ], [

            'area_lote.required' => 'El área del lote es obligatoria.',
            'area_lote.numeric' => 'El área del lote debe ser un número.',
            'metros_cama.required' => 'Los metros de cama son obligatorios.',
            'metros_cama.numeric' => 'Los metros de cama deben ser un número.',
            'metros_cama.max' => 'El número es demasiado grande, maximo 5 digitos y 3 decimales.',
            'evaluadorSeleccionado.nombre.required' => 'Debe seleccionar un evaluador.',
            'evaluadorSeleccionado.id.required' => 'Debe proporcionar un ID de evaluador.',
            'evaluadorSeleccionado.id.exists' => 'El evaluador seleccionado no es válido.',
            'fecha.required' => 'La fecha es obligatoria.',
            'fecha.date' => 'La fecha debe ser una fecha válida.',
            'tipo_evaluacion.required' => 'El tipo de evaluación es obligatorio.',
            'campoSeleccionado.required' => 'Debe seleccionar un campo.',
        ]);

        try {
            // Construcción del array de datos
            $data = [
                'area_lote' => $this->area_lote,
                'metros_cama' => $this->metros_cama,
                'evaluador' => $this->evaluadorSeleccionado['nombre'],
                'empleado_id' => $this->evaluadorSeleccionado['id'],
                'fecha' => $this->fecha,
                'campania_id' => $this->campania->id,
                'tipo_evaluacion' => $this->tipo_evaluacion
            ];

            $message = '';

            if ($this->poblacionPlantaId) {
                // Si existe, actualiza el registro
                PoblacionPlantas::where('id', $this->poblacionPlantaId)->update($data);
                $message = 'Registro actualizado correctamente.';
            } else {
                // Si no existe, inserta un nuevo registro
                $poblacionPlanta = PoblacionPlantas::create($data);
                $this->poblacionPlantaId = $poblacionPlanta->id;
                $message = 'Registro exitoso de Población de plantas.';
            }

            if ($this->poblacionPlantaId) {
                $datosValidados = collect($datos)->filter(function ($fila) {
                    return !empty($fila['cama_muestreada']) && !empty($fila['longitud_cama']) && !empty($fila['plantas_x_cama']);
                });
                if ($datosValidados->count() !== count($datos)) {
                    return $this->alert('error', 'En el detalle algunas filas tienen campos obligatorios vacíos.');
                }

                if (count($datos) != 0) {
                    PoblacionPlantasDetalle::where('poblacion_plantas_id', $this->poblacionPlantaId)->delete();

                    // Insertar datos en la base de datos en un solo query
                    PoblacionPlantasDetalle::insert($datosValidados->map(function ($fila) {
                        return [
                            'poblacion_plantas_id' => $this->poblacionPlantaId,
                            'cama_muestreada' => is_numeric($fila['cama_muestreada']) ? (int) $fila['cama_muestreada'] : null,
                            'longitud_cama' => is_numeric($fila['longitud_cama']) ? (float) $fila['longitud_cama'] : null,
                            'plantas_x_cama' => is_numeric($fila['plantas_x_cama']) ? (int) $fila['plantas_x_cama'] : null,
                            'plantas_x_metro' => is_numeric($fila['plantas_x_metro']) ? (float) $fila['plantas_x_metro'] : null,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];
                    })->toArray());

                    $this->asignarPromedios();
                }
            }

            $this->alert('success', $message);
            $this->enviarHistorialPoblacionPlantas($this->campania->id);
            $this->dispatch('poblacionPlantasRegistrado');
        } catch (\Throwable $th) {
            $this->dispatch('log', $th->getMessage());
            $this->alert('error', 'Ocurrió un error interno al procesar la solicitud.');
        }
    }*/
    public function enviarHistorialPoblacionPlantas($campaniaId)
    {
        try {
            $campaniaServicio = new CampaniaServicio($campaniaId);
            $campaniaServicio->registrarHistorialPoblacionPlantas();
        } catch (\Throwable $th) {
            throw $th;
        }
    }
    public function asignarPromedios()
    {
        $this->reset(['promedioPlantasXCama', 'promedioPlantasXMetro', 'promedioPlantasHA']);
        $poblacionPlanta = PoblacionPlantas::find($this->poblacionPlantaId);

        if (!$poblacionPlanta) {
            return;
        }
        // Obtener los promedios con avg()
        $this->promedioPlantasXCama = $poblacionPlanta->promedio_plantas_x_cama;
        $this->promedioPlantasXMetro = $poblacionPlanta->promedio_plantas_x_metro;
        $this->promedioPlantasHA = $poblacionPlanta->promedio_plantas_ha;
    }

    public function editarPoblacionPlanta($poblacionId)
    {
        try {
            $this->resetearCampos();
            $poblacionPlantas = PoblacionPlantas::findOrFail($poblacionId);
            $this->campania = CampoCampania::findOrFail($poblacionPlantas->campania_id);

            $this->campoSeleccionado = $poblacionPlantas->campania->campo;
            $this->poblacionPlantaId = $poblacionPlantas->id;
            $this->area_lote = $poblacionPlantas->area_lote;
            $this->metros_cama = $poblacionPlantas->metros_cama;
            $this->fecha = $poblacionPlantas->fecha;
            $this->tipo_evaluacion = $poblacionPlantas->tipo_evaluacion;

            // Si existe el evaluador, asignarlo
            $this->evaluadorSeleccionado = [
                'nombre' => $poblacionPlantas->evaluador,
                'id' => $poblacionPlantas->empleado_id
            ];

            $this->listaCamasMuestreadas = $poblacionPlantas->detalles->map(function ($detalle) {
                return [
                    'cama_muestreada' => $detalle->cama_muestreada,
                    'longitud_cama' => $detalle->longitud_cama,
                    'plantas_x_cama' => $detalle->plantas_x_cama,
                    'plantas_x_metro' => round($detalle->plantas_x_metro,0)
                ];
            })->toArray();

            $this->asignarPromedios();
            $this->dispatch('cargarData', $this->listaCamasMuestreadas);

            $this->mostrarFormulario = true;

        } catch (\Throwable $th) {
            $this->alert('error', 'El registro ya no existe.');
        }
    }

    public function updatedEvaluador()
    {
        $this->evaluadores = PlanEmpleado::whereRaw(
            "CONCAT(nombres, ' ', apellido_paterno, ' ', apellido_materno) LIKE ?",
            ["%{$this->evaluador}%"]
        )
            ->limit(5)
            ->get()
            ->map(function ($evaluador) {
                return [
                    'id' => $evaluador->id,
                    'nombres' => $evaluador->nombre_completo
                ];
            })
            ->toArray();
    }
    public function seleccionarEvaluador($id, $nombre)
    {
        $this->reset(['evaluador', 'evaluadores']);
        $this->evaluadorSeleccionado = [
            'id' => $id,
            'nombre' => $nombre,
        ];
    }
    public function quitarEvaluador()
    {
        $this->reset(['evaluadorSeleccionado']);
    }

    public function resetearCampos()
    {
        $this->reset(['poblacionPlantaId', 'area_lote', 'metros_cama', 'evaluadorSeleccionado', 'fecha', 'tipo_evaluacion', 'campania', 'campoSeleccionado']);
        $this->resetErrorBag();
        $this->listaCamasMuestreadas = [];
        $this->asignarPromedios();
        $this->fecha = Carbon::now()->format('Y-m-d');
        $this->dispatch('cargarData', $this->listaCamasMuestreadas);
    }
    public function agregarEvaluacion($campaniaId = null)
    {
        try {
            $this->resetearCampos();

            if ($campaniaId) {
                $campania = CampoCampania::find($campaniaId);
                if ($campania) {
                    $this->campoSeleccionado = $campania->campo;
                    $this->campania = $campania;
                    $this->buscarArea();
                }
            }
            $this->mostrarFormulario = true;
        } catch (\Throwable $th) {
            return $this->alert('error', 'Ocurrió un error, tal vez la campaña no exista.');
        }
    }

    public function render()
    {
        return view('livewire.reporte-campo-poblacion-planta-form-component');
    }
}

<?php

namespace App\Livewire;

use App\Models\Actividad;
use App\Models\Campo;
use App\Models\Labores;
use App\Models\Recogidas;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;

class ActividadesFormComponent extends Component
{
    use LivewireAlert;
    public $mostrarFormulario = false;
    public $laborSeleccionada;
    public $campoSeleccionado;
    public $valoracion;
    public $fecha;
    public $campos;
    public $labores;
    public $actividades = [];
    public $horas_trabajadas;
    public $actividadId;
    protected $listeners = ['registrarActividad', 'editarActividad'];
    public function mount()
    {
        $this->inicializarValores();
    }
    public function registrarLaborCuadrilla()
    {
        $this->validate(
            [
                'fecha' => 'required|date',
                'campoSeleccionado' => 'required|string',
                'laborSeleccionada' => 'required|integer|exists:labores,id',
                'horas_trabajadas' => 'required|numeric'
            ],
            [
                'fecha.required' => 'El campo "Fecha" es obligatorio.',
                'fecha.date' => 'El campo "Fecha" debe ser una fecha válida.',

                'campoSeleccionado.required' => 'El campo "Campo" es obligatorio.',
                'campoSeleccionado.string' => 'El campo "Campo" debe ser un texto válido.',

                'laborSeleccionada.required' => 'El campo "Labor" es obligatorio.',
                'laborSeleccionada.integer' => 'El campo "Labor" debe ser un número válido.',
                'laborSeleccionada.exists' => 'La labor seleccionada no existe en la base de datos.',

                'horas_trabajadas.required' => 'Las horas trabajadas son requeridas.',
                'horas_trabajadas.numeric' => 'Valor numérico inválido.',
            ]
        );
        try {
            $data = [
                'fecha' => $this->fecha,
                'campo' => $this->campoSeleccionado,
                'labor_id' => $this->laborSeleccionada,
                'horas_trabajadas' => $this->horas_trabajadas,
                'labor_valoracion_id'=>null
            ];
            if ($this->valoracion) {
                $data['labor_valoracion_id'] = $this->valoracion->id;
            }

            $actividad = null;

            if ($this->actividadId) {
                $actividad = Actividad::find($this->actividadId);
                if ($actividad) {
                    $actividad->update($data);
                    
                    foreach ($actividad->recogidas as $recogidaO) {
                        if (!isset($this->actividades[$recogidaO->recogida_numero - 1])) {
                            $recogidaO->delete();
                        }
                    }
                }
            } else {
                $actividad = Actividad::create($data);
            }

            //$actividad->recogidas()->delete();
            if (count($this->actividades) > 0) {
                foreach ($this->actividades as $indice => $actividadArray) {
                    $horas = $actividadArray['horas'] ?? 0;
                    $kg = $actividadArray['kg'] ?? 0;

                    $recogida = Recogidas::where('recogida_numero', $indice + 1)
                        ->where('actividad_id', $actividad->id)
                        ->first();

                    if ($recogida) {
                        $recogida->update([
                            'horas' => $horas,
                            'kg_estandar' => $kg,
                        ]);
                    } else {
                        Recogidas::create([
                            'recogida_numero' => $indice + 1,
                            'actividad_id' => $actividad->id,
                            'horas' => $horas,
                            'kg_estandar' => $kg,
                        ]);
                    }
                }
            }

            $this->dispatch('actividadRegistrada');
            $this->resetForm();
            $this->mostrarFormulario = false;
            $this->alert('success', 'Registro de Labor exitoso.');
        } catch (\Throwable $th) {
            $this->dispatch('log', $th->getMessage());
            $this->alert('error', 'Ocurrió un error interno (CALC RLC).');
        }
    }
    public function editarActividad($actividadId)
    {
        $this->resetForm();

        $actividad = Actividad::find($actividadId);
        if (!$actividad) {
            return $this->alert('error', 'La actividad ya no existe.');
        }
        
        $this->fecha =  $actividad->fecha;
        $this->laborSeleccionada =  $actividad->labor_id;
        $this->campoSeleccionado =  $actividad->campo;
        $this->horas_trabajadas =  $actividad->horas_trabajadas;
        $this->actividadId = $actividad->id;
        $this->revisarValoraciones();
        $this->actividades = [];
        if ($actividad->recogidas && $actividad->recogidas->count() > 0) {
            foreach ($actividad->recogidas as $recogida) {
                $this->actividades[] = [
                    'horas' => $recogida->horas,
                    'kg' => $recogida->kg_estandar,
                ];
            }
        }
        $this->mostrarFormulario = true;
    }
    public function registrarActividad($fecha)
    {
        $this->resetForm();
        $this->fecha = $fecha;
        $this->mostrarFormulario = true;
    }
    public function resetForm()
    {
        $this->inicializarValores();
        $this->actividades = [];
        $this->reset(['horas_trabajadas','actividadId']);
    }
    public function quitarActividad($indice)
    {
        unset($this->actividades[$indice]);
    }
    
    
    public function inicializarValores()
    {
        $this->labores = Labores::orderBy('nombre_labor')->get();
        $this->campos = Campo::orderBy('nombre')->get();

        if ($this->campos->count() > 0) {
            $this->campoSeleccionado = $this->campos->first()->nombre;
        }
        $this->laborSeleccionada = null;
    }
    public function updatedLaborSeleccionada()
    {
        $this->revisarValoraciones();
    }
    public function revisarValoraciones()
    {
        if (!$this->fecha) {
            return;
        }
        if (!$this->laborSeleccionada) {
            return;
        }
        $labor = Labores::find($this->laborSeleccionada);
        if (!$labor) {
            return $this->alert('error', 'La labor seleccionada ya no existe.');
        }
        $this->valoracion = $labor->valoraciones()->orderBy('vigencia_desde', 'desc')->whereDate('vigencia_desde', "<=", $this->fecha)
            ->first();

        if (!$this->valoracion) {
            return;
        }
        $this->actividades = [];
        $this->agregarActividad();
        $this->agregarActividad();

        // Recalcular para todas las actividades cuando cambia la labor seleccionada
        foreach ($this->actividades as $indice => $actividad) {
            $horas = $actividad['horas'] ?? 0;
            $this->recalcularKg($indice, (float)$horas);
        }
    }
    public function agregarActividad()
    {
        $this->actividades[] = [
            'horas' => 0,
            'kg' => 0
        ];
    }
    public function updatedActividades($valor, $clave)
    {
        $indice = explode('.', $clave)[0];
        $campo = explode('.', $clave)[1];

        if ($campo === 'horas') {
            $this->recalcularKg($indice, (float)$valor);
        }
        $this->horas_trabajadas = collect($this->actividades)->sum('horas');
    }
    private function recalcularKg($indice, $horas)
    {
        if (!$this->valoracion || !$this->laborSeleccionada) {
            return; // Salir si no hay datos válidos
        }

        if (isset($this->actividades[$indice])) {
            $kgPorHora = $this->valoracion->kg_8 / 8;
            $this->actividades[$indice]['kg'] = max(0, $horas) * $kgPorHora;
        }
    }
    public function render()
    {
        return view('livewire.actividades-form-component');
    }
}

<?php

namespace App\Livewire;

use App\Models\Campo;
use App\Models\CampoCampania;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;

class CampoCampaniaFormComponent extends Component
{
    use LivewireAlert;
    public $mostrarFormulario = false;
    public $fechaInicio;
    public $nombreCampania;
    public $camposSeleccionados = [];
    public $errorMensaje = [];
    public $ultimaCampania;
    protected $listeners = ['registroCampania'];
    public function registroCampania($campoNombre)
    {
        $this->resetForm();
        $campo = Campo::find($campoNombre);
        if ($campo) {
            $this->ultimaCampania = $campo->CampaniaActual;
        }

        $this->mostrarFormulario = true;
        $this->camposSeleccionados[] = $campoNombre;
    }
    public function resetForm()
    {
        $this->reset(['camposSeleccionados', 'errorMensaje', 'nombreCampania', 'ultimaCampania']);
        $this->fechaInicio = Carbon::now()->format('Y-m-d');
    }
    public function store()
    {
        $this->validate([
            'nombreCampania' => 'required|string',
            'fechaInicio' => 'required|date',
        ], [
            'nombreCampania.required' => 'El nombre de la campaña es obligatorio.',
            'fechaInicio.required' => 'La fecha de inicio es obligatorio.',
            'fechaInicio.date' => 'La fecha no tiene un formato válido.'
        ]);
        try {
            $this->errorMensaje = [];
            $registrosInsertados = 0;

            foreach ($this->camposSeleccionados as $campo) {
                # Revisar si hay una campaña anterior
                # Si hay, se debe obtener la ultima anteior para actualizar la fecha final a un dia antes de la fecha seleccionada
                $campaniaAnterior = CampoCampania::whereDate('fecha_inicio', '<', $this->fechaInicio)->orderBy('fecha_inicio')->first();
                $campaniaPosterior = CampoCampania::whereDate('fecha_inicio', '>', $this->fechaInicio)->orderBy('fecha_inicio')->first();
                $fecha = Carbon::parse($this->fechaInicio)->addDay(-1);
                if ($campaniaAnterior) {
                    $campaniaAnterior->update([
                        'fecha_fin' => $fecha
                    ]);
                }

                $data = [
                    'nombre_campania' => mb_strtoupper($this->nombreCampania),
                    'campo' => $campo,
                    'fecha_inicio' => $this->fechaInicio,
                    'usuario_modificador' => Auth::id()
                ];

                if ($campaniaPosterior) {
                    $fechaFin = Carbon::parse($campaniaPosterior->fecha_inicio)->addDay(-1);
                    $data['fecha_fin'] = $fechaFin;
                }
                $campoCampania = CampoCampania::whereDate('fecha_inicio', $this->fechaInicio)->where('campo', $campo)->first();
                if (!$campoCampania) {
                    CampoCampania::create($data);
                    $registrosInsertados++;
                } else {
                    $this->errorMensaje[] = "Ya existe una Campaña para el campo {$campo} en la fecha {$campoCampania->fecha_inicio} llamada {$campoCampania->nombre_campania}";
                    continue;
                }
            }
            if ($registrosInsertados > 0) {
                if (count($this->errorMensaje) > 0) {
                    $this->alert('success', 'Algunas campañas se registraron correctamente.');
                } else {
                    $this->alert('success', 'Todas las campañas se registraron correctamente.');
                }
                $this->resetForm();
                $this->mostrarFormulario = false;
                $this->dispatch('campaniaInsertada');
            }
        } catch (\Throwable $th) {
            $this->alert('error', 'Ocurrió un error inesperado #ccfc1.');
            $this->dispatch('log', $th->getMessage());
        }
    }
    public function render()
    {
        return view('livewire.campo-campania-form-component');
    }
}

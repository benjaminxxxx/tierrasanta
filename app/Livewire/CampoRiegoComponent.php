<?php

namespace App\Livewire;

use App\Models\Campo;
use App\Models\ConsolidadoRiego;
use App\Models\Cuadrillero;
use App\Models\Empleado;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;

class CampoRiegoComponent extends Component
{
    use LivewireAlert;
    public $campos;
    public $regadores;
    public $fecha;
    public $camposSeleccionados = [];
    public $regadorSeleccionado;
    public $tipoPersonal;
    public $estaConsolidado;
    protected $listeners = ['RefrescarMapa','desconsolidacion'=>'$refresh'];
    public function mount()
    {
        $this->fecha = (new \DateTime('now'))->format('Y-m-d');
        $this->tipoPersonal = 'regadores';
    }
    public function RefrescarMapa()
    {
        $this->camposSeleccionados = [];
        $this->render();
    }

    public function consolidar()
    {
        $this->dispatch('ConsolidarRegadores',$this->fecha);
    }
    public function render()
    {
        $consolidadoExiste = ConsolidadoRiego::where('fecha',$this->fecha)->first();
        if($consolidadoExiste){
            $this->estaConsolidado = ConsolidadoRiego::where('fecha',$this->fecha)->where('estado','noconsolidado')->exists()?false:true;
        }else{
            $this->estaConsolidado = false;
        }
        
        
        if ($this->tipoPersonal) {

            switch ($this->tipoPersonal) {
                case 'empleados':
                    // Estandarizamos los campos a 'nombre_completo' y 'documento'
                    $this->regadores = Empleado::orderBy('apellido_paterno')
                        ->orderBy('apellido_materno')
                        ->orderBy('nombres')
                        ->get()
                        ->map(function ($empleado) {
                            return [
                                'nombre_completo' => $empleado->nombres . ' ' . $empleado->apellido_paterno . ', ' . $empleado->apellido_materno,
                                'documento' => $empleado->documento, // Asumiendo que el campo es 'documento'
                            ];
                        });
                    break;

                case 'cuadrilleros':
                    // Estandarizamos los campos a 'nombre_completo' y 'documento'
                    $this->regadores = Cuadrillero::orderBy('nombre_completo')
                        ->whereNotNull('dni')
                        ->get(['dni as documento', 'nombre_completo'])
                        ->map(function ($cuadrillero) {
                            return [
                                'nombre_completo' => $cuadrillero->nombre_completo,
                                'documento' => $cuadrillero->documento, // 'dni' renombrado como 'documento'
                            ];
                        });
                    break;

                default:
                    $this->regadores = Empleado::where('cargo_id', 'RIE')
                        ->orderBy('apellido_paterno')
                        ->orderBy('apellido_materno')
                        ->orderBy('nombres')
                        ->get()
                        ->map(function ($empleado) {
                            return [
                                'nombre_completo' => $empleado->nombres . ' ' . $empleado->apellido_paterno . ', ' . $empleado->apellido_materno,
                                'documento' => $empleado->documento, // Asumiendo que el campo es 'documento'
                            ];
                        });
                    break;
            }

        }
        $this->campos = Campo::all();
        return view('livewire.campo-riego-component');
    }

    public function verRiego()
    {
        if (!$this->regadorSeleccionado || !$this->fecha) {
            return;
        }
        $empleados = $this->regadores->pluck('nombre_completo', 'documento');

        $data = [
            'fecha' => $this->fecha,
            'regador' => $this->regadorSeleccionado,
            'regadorNombre' => $empleados[$this->regadorSeleccionado],
            'campos' => $this->camposSeleccionados,
            'tipoPersonal' => $this->tipoPersonal
        ];

        // Despachar el evento con los datos obtenidos
        $this->dispatch('asignarCargarRegadorHoras', $data);
    }
    public function verObservaciones()
    {
        if (!$this->regadorSeleccionado || !$this->fecha) {
            return;
        }
        $empleados = $this->regadores->pluck('nombre_completo', 'documento');

        $data = [
            'fecha' => $this->fecha,
            'regador' => $this->regadorSeleccionado,
            'regadorNombre' => $empleados[$this->regadorSeleccionado],
            'tipoPersonal' => $this->tipoPersonal
        ];

        // Despachar el evento con los datos obtenidos
        $this->dispatch('asignarObservacionesHoras', $data);
    }
    public function seleccionarCampo($nombreCampo, $puedeSeleccionarse = true)
    {
        if (!$puedeSeleccionarse) {
            return;
        }

        // Buscar si el campo ya está seleccionado
        $key = array_search($nombreCampo, array_column($this->camposSeleccionados, 'nombre'));

        if ($key !== false) {
            // Si el campo ya está seleccionado, eliminarlo del array
            unset($this->camposSeleccionados[$key]);
            $this->camposSeleccionados = array_values($this->camposSeleccionados); // Reindexa el array
        } else {
            // Si el campo no está seleccionado, agregarlo con hora_inicio y hora_fin vacíos
            $this->camposSeleccionados[] = [
                'nombre' => $nombreCampo,
                'inicio' => null,
                'fin' => null,
                'total' => null
            ];
        }
    }
}

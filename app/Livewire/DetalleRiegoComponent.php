<?php

namespace App\Livewire;

use App\Models\ConsolidadoRiego;
use App\Models\Cuadrillero;
use App\Models\DetalleRiego;
use App\Models\Empleado;
use App\Models\HorasAcumuladas;
use App\Models\Observacion;
use Livewire\Component;

class DetalleRiegoComponent extends Component
{
    public $fecha;
    public $regadores;
    public $tipoPersonal;
    public $riegos = [];
    public $regadorSeleccionado;
    public $estaConsolidado;
    public $consolidados;
    public $search;
    protected $listeners = ['RefrescarMapa'=>'$refresh','desconsolidacion'=>'$refresh'];
    public function mount()
    {
        $this->fecha = (new \DateTime('now'))->format('Y-m-d');
        $this->tipoPersonal = 'regadores';

        $this->obtenerRiegos();

        // 6. Depuración para ver el resultado
        //dd($this->riegos);
    }
   
    private function obtenerRiegos()
    {
        if(!$this->fecha){
            return;
        }
//where $this->search when diferente de vacio o null
        $this->consolidados = ConsolidadoRiego::whereDate('fecha',$this->fecha)->get();
       /* $this->consolidados = ConsolidadoRiego::whereDate('fecha', $this->fecha)
        ->where(function ($query) {
            $query->where('regador_nombre', 'like', '%' . $this->search . '%');
        })
        ->get();
*/
        

        if($this->consolidados->count()!=0){
            return;
        }

        // 1. Cargar todos los regadores por defecto
        $regadores = Empleado::where('cargo_id', 'RIE')
            ->orderBy('apellido_paterno')
            ->orderBy('apellido_materno')
            ->orderBy('nombres')
            ->get()
            ->map(function ($empleado) {
                return [
                    'nombre_completo' => $empleado->apellido_paterno . ' ' . $empleado->apellido_materno . ', ' . $empleado->nombres,
                    'documento' => $empleado->documento,
                ];
            });

        // 2. Consultar en DetalleRiego, Observacion, y HorasAcumuladas para obtener documentos únicos

        // De DetalleRiego
        $detalleRiegoDocumentos = DetalleRiego::whereDate('fecha', $this->fecha)
            ->pluck('regador'); // El campo 'regador' es el documento en DetalleRiego

        // De Observacion
        $observacionDocumentos = Observacion::whereDate('fecha', $this->fecha)
            ->pluck('documento'); // El campo 'documento' es el DNI en Observacion

        // De HorasAcumuladas
        $horasAcumuladasDocumentos = HorasAcumuladas::whereDate('fecha_uso', $this->fecha)
            ->pluck('documento'); // El campo 'documento' es el DNI en HorasAcumuladas

        // 3. Unir todos los documentos en una colección
        $todosDocumentos = $detalleRiegoDocumentos
            ->merge($observacionDocumentos)
            ->merge($horasAcumuladasDocumentos)
            ->unique(); // Evitar duplicados de documentos

        // 4. Procesar todos los documentos recolectados
        $otrosEmpleados = $todosDocumentos->map(function ($documento) {
            return [
                'nombre_completo' => $this->obtenerNombreRegador($documento), // Usar la función para obtener nombre
                'documento' => $documento,
            ];
        });

        // 5. Hacer merge con los regadores, evitando duplicados basados en 'documento'
        $this->riegos = $regadores->merge($otrosEmpleados)
            ->unique('documento') // Evitar duplicados basados en el campo 'documento'
            ->pluck('nombre_completo', 'documento')
            ->toArray();

        foreach ($this->riegos as $documento => $nombreRegador) {
            $consolidado = ConsolidadoRiego::where('regador_documento', $documento)->whereDate('fecha', $this->fecha)->first();

            if(!$consolidado){
                $this->crearConsolidado($documento);
            }
        }

        $this->consolidados = ConsolidadoRiego::whereDate('fecha',$this->fecha)->get();
    }
    private function crearConsolidado($documento){

        $nombreRegador = $this->obtenerNombreRegador($documento);

        ConsolidadoRiego::create([
            'regador_documento' => $documento,
            'regador_nombre' => $nombreRegador,
            'fecha' => $this->fecha,
            'hora_inicio' => null,
            'hora_fin' => null,
            'total_horas_riego' => 0,
            'total_horas_observaciones' => 0,
            'total_horas_acumuladas' => 0,
            'total_horas_jornal' => 0, 
            'estado' => 'noconsolidado',
        ]);
    }
    public function render()
    {
        $consolidadoExiste = ConsolidadoRiego::where('fecha',$this->fecha)->first();
        if($consolidadoExiste){
            $this->estaConsolidado = ConsolidadoRiego::where('fecha',$this->fecha)->where('estado','noconsolidado')->exists()?false:true;
        }else{
            $this->estaConsolidado = false;
        }
        
        $documentosAgregados =array_keys(ConsolidadoRiego::where('fecha',$this->fecha)->pluck('regador_documento','regador_documento')->toArray());

        if ($this->tipoPersonal) {

            switch ($this->tipoPersonal) {
                case 'empleados':
                    $this->regadores = Empleado::whereNotIn('documento', $documentosAgregados)
                        ->orderBy('apellido_paterno')
                        ->orderBy('apellido_materno')
                        ->orderBy('nombres')
                        ->get()
                        ->map(function ($empleado) {
                            return [
                                'nombre_completo' => $empleado->apellido_paterno . ' ' . $empleado->apellido_materno . ', ' . $empleado->nombres,
                                'documento' => $empleado->documento,
                            ];
                        });
                    break;

                case 'cuadrilleros':
                    $this->regadores = Cuadrillero::whereNotIn('dni', $documentosAgregados)
                        ->whereNotNull('dni')
                        ->orderBy('nombre_completo')
                        ->get(['dni as documento', 'nombre_completo'])
                        ->map(function ($cuadrillero) {
                            return [
                                'nombre_completo' => $cuadrillero->nombre_completo,
                                'documento' => $cuadrillero->documento,
                            ];
                        });
                    break;

                default:
                    $this->regadores = Empleado::where('cargo_id', 'RIE')
                        ->whereNotIn('documento', $documentosAgregados)
                        ->orderBy('apellido_paterno')
                        ->orderBy('apellido_materno')
                        ->orderBy('nombres')
                        ->get()
                        ->map(function ($empleado) {
                            return [
                                'nombre_completo' => $empleado->apellido_paterno . ' ' . $empleado->apellido_materno . ', ' . $empleado->nombres,
                                'documento' => $empleado->documento,
                            ];
                        });
                    break;
            }

        }
        return view('livewire.detalle-riego-component');
    }
    public function consolidar()
    {
        $this->dispatch('ConsolidarRegadores',$this->fecha);
    }
    public function agregarDetalle()
    {
/*
        if (!array_key_exists($this->regadorSeleccionado, $this->riegos)) {
            $this->riegos[$this->regadorSeleccionado] = $this->obtenerNombreRegador($this->regadorSeleccionado);
            
        }*/
        $this->crearConsolidado($this->regadorSeleccionado);
            $this->regadorSeleccionado = null;
            $this->obtenerRiegos();
    }
    private function obtenerNombreRegador($documento)
    {
        return optional(Empleado::where('documento', $documento)->first())->nombre_completo
            ?? Cuadrillero::where('dni', $documento)->value('nombre_completo')
            ?? 'NN';
    }
    public function updatedFecha()
    {
        $this->obtenerRiegos();
    }
    public function fechaAnterior()
    {
        // Restar un día a la fecha seleccionada
        $this->fecha = \Carbon\Carbon::parse($this->fecha)->subDay()->format('Y-m-d');
        $this->obtenerRiegos();
    }

    public function fechaPosterior()
    {
        // Sumar un día a la fecha seleccionada
        $this->fecha = \Carbon\Carbon::parse($this->fecha)->addDay()->format('Y-m-d');
        $this->obtenerRiegos();
    }
}

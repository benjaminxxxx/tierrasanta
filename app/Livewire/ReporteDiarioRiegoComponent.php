<?php

namespace App\Livewire;

use App\Models\ConsolidadoRiego;
use App\Models\Cuadrillero;
use App\Models\Empleado;
use Livewire\Component;

class ReporteDiarioRiegoComponent extends Component
{
    public $fecha;
    public $consolidados;
    public function mount(){

        $this->fecha = (new \DateTime('now'))->format('Y-m-d');
        $this->obtenerRiegos();
    }
    public function render()
    {
        return view('livewire.reporte-diario-riego-component');
    }
    public function obtenerRiegos(){

        if (!$this->fecha) {
            return;
        }

        $this->consolidados = ConsolidadoRiego::whereDate('fecha', $this->fecha)->get();

        if ($this->consolidados->count() != 0) {
            return;
        }

        $empleados = Empleado::where('cargo_id', 'RIE')
            ->orderBy('apellido_paterno')
            ->orderBy('apellido_materno')
            ->orderBy('nombres')
            ->get()
            ->map(function ($empleado) {
                $nombre_completo = $empleado->apellido_paterno . ' ' . $empleado->apellido_materno . ', ' . $empleado->nombres;
                $documento = $empleado->documento;

                // Buscar el consolidado dentro del map
                $consolidado = ConsolidadoRiego::where('regador_documento', $documento)
                                               ->whereDate('fecha', $this->fecha)
                                               ->first();

                // Si no existe el consolidado, lo creamos
                if (!$consolidado) {
                    $this->crearConsolidado($documento,$nombre_completo);
                }

                return [
                    'nombre_completo' => $nombre_completo,
                    'documento' => $documento,
                ];
            });

        if($empleados->count()==0){
            return;
        }

        $this->obtenerRiegos();
    }
    private function crearConsolidado($documento,$nombre_completo)
    {

        ConsolidadoRiego::create([
            'regador_documento' => $documento,
            'regador_nombre' => $nombre_completo,
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

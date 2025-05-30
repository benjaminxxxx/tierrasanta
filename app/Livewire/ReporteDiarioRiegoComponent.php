<?php

namespace App\Livewire;

use App\Models\ConsolidadoRiego;
use App\Models\Cuadrillero;
use App\Models\Empleado;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;

class ReporteDiarioRiegoComponent extends Component
{
    use LivewireAlert;
    public $fecha;
    public $consolidados;
    public $archivoBackupHoy;
    public $tipoLabores;
    public $tipoPersonal;
    public $regadores;
    public $regadorSeleccionado;
    protected $listeners = ["generalActualizado",'obtenerRiegos'];
    public function mount()
    {

        $this->fecha = (new \DateTime('now'))->format('Y-m-d');
        $this->tipoPersonal = 'regadores';
        $this->obtenerRiegos();
        $this->obtenerMasRegadores();
    }
    public function render()
    {
        return view('livewire.reporte-diario-riego-component');
    }
    public function generalActualizado(){
        $this->dispatch('delay-riegos');
    }
    public function updatedTipoPersonal()
    {
        $this->obtenerMasRegadores();
    }
    public function obtenerMasRegadores()
    {
        if ($this->tipoPersonal) {

            $documentosAgregados = array_keys(ConsolidadoRiego::where('fecha', $this->fecha)->pluck('regador_documento', 'regador_documento')->toArray());

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
                            ->orderBy('nombres')
                            ->get(['dni as documento', 'nombres'])
                            ->map(function ($cuadrillero) {
                                return [
                                    'nombre_completo' => $cuadrillero->nombres,
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

        }
    }
    public function agregarDetalle()
    {
        if (!$this->regadorSeleccionado) {
            return $this->alert('error', 'Debe seleccionar un Regador');
        }
        $this->crearConsolidado($this->regadorSeleccionado);
        $this->regadorSeleccionado = null;
        $this->obtenerRiegos();
    }
   
    public function obtenerRiegos()
    {

        if (!$this->fecha) {
            return;
        }
        //$this->alert('success','ss');

        $this->consolidados = ConsolidadoRiego::whereDate('fecha', $this->fecha)->get();

        $this->dispatch('$refresh')->self();

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
                    $this->crearConsolidado($documento, $nombre_completo);
                }

                return [
                    'nombre_completo' => $nombre_completo,
                    'documento' => $documento,
                ];
            });

        if ($empleados->count() == 0) {
            return;
        }

        $this->obtenerRiegos();
    }
    private function crearConsolidado($documento, $nombre_completo=null)
    {
        if(!$nombre_completo){
            $nombre_completo = $this->obtenerNombreRegador($documento);
        }

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
            ?? Cuadrillero::where('dni', $documento)->value('nombres')
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
    public function descargarBackup(){
        
        $this->dispatch('RDRIE_descargarPorFecha',$this->fecha);
    }
    public function descargarBackupCompleto(){
        
        $this->dispatch('RDRIE_descargarBackupCompleto');
    }
    
}

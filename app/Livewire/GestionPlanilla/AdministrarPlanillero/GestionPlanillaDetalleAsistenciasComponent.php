<?php

namespace App\Livewire\GestionPlanilla\AdministrarPlanillero;

use App\Services\Handsontable\HSTPlanillaAsistencia;
use App\Services\Handsontable\HSTPlanillaRegistroDiarioActividades;
use Carbon\Carbon;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;

class GestionPlanillaDetalleAsistenciasComponent extends Component
{
    use LivewireAlert;
    public $mes;
    public $anio;
    public $empleados = [];
    public $informacionAsistenciaAdicional = [];
    public $dias = 30;
    public function mount($mes,$anio){
        $this->mes = $mes;
        $this->anio = $anio;
        $this->dias = $this->obtenerDiasDelMesConTitulo($this->anio,$this->mes);
        $this->obtenerAsistencias();
    }
    public function obtenerAsistencias(){
        try {
            
            $this->empleados = app(HSTPlanillaAsistencia::class)->obtenerAsistenciaMensualAgraria($this->mes,$this->anio);
            $this->informacionAsistenciaAdicional = app(HSTPlanillaAsistencia::class)->obtenerInformacionAsistenciaAdicional($this->mes,$this->anio);

        } catch (\Throwable $th) {
            $this->alert('error',$th->getMessage());
        }
    }
    public function obtenerDiasDelMesConTitulo($anio, $mes)
    {
        $diasConTitulo = [];

        // Obtiene el número total de días en el mes
        $ultimoDiaMes = Carbon::createFromDate($anio, $mes)->endOfMonth()->day;

        // Recorre cada día del mes
        for ($dia = 1; $dia <= $ultimoDiaMes; $dia++) {
            // Obtiene el día de la semana (Lunes, Martes, etc.)
            $fecha = Carbon::createFromDate($anio, $mes, $dia);
            $diaSemana = $fecha->format('N'); // 1 para Lunes, 7 para Domingo

            // Array de títulos para los días de la semana
            $diasTitulo = ['L', 'M', 'M', 'J', 'V', 'S', 'D'];

            // Guarda en el array el título del día y el índice
            $diasConTitulo[] = [
                'titulo' => $diasTitulo[$diaSemana - 1], // -1 porque el índice del array comienza en 0
                'indice' => $dia
            ];
        }

        return $diasConTitulo;
    }
    public function render()
    {
        return view('livewire.gestion-planilla.administrar-planillero.gestion-planilla-detalle-asistencias');
    }
}
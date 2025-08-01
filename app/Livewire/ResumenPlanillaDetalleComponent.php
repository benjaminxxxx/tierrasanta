<?php

namespace App\Livewire;

use App\Models\ReporteDiario;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;

class ResumenPlanillaDetalleComponent extends Component
{
    use LivewireAlert;
    public $anio;
    public $mes;
    public $empleadosData = [];
    public $empleadosGeneral = [];
    public $fechas = [];
    public $diasMes = [];
    public $esDias = [];
   
    public function mount()
    {
        $this->esDias = [
            'Mon' => 'Lunes',
            'Tue' => 'Martes',
            'Wed' => 'Miércoles',
            'Thu' => 'Jueves',
            'Fri' => 'Viernes',
            'Sat' => 'Sábado',
            'Sun' => 'Domingo',
        ];
        $fechaInicio = Carbon::createFromDate($this->anio, $this->mes, 1);
        $diasEnMes = $fechaInicio->daysInMonth();
        $fechaFin = Carbon::createFromDate($this->anio, $this->mes, $diasEnMes);
        $periodo = CarbonPeriod::create($fechaInicio, $fechaFin);

        foreach ($periodo as $fecha) {
            $this->diasMes[$fecha->day] = $fecha;
        }

        $empleados = ReporteDiario::with('detalles','detalles.labores')->whereBetween('fecha', [$fechaInicio, $fechaFin])->get();
       
        if ($empleados) {
            $this->empleadosGeneral = $empleados->keyBy('documento')->values();
            foreach ($empleados as $empleado) {
                $this->empleadosData[$empleado->documento][$empleado->fecha] = $empleado;
            }
        }
    }
    
    public function render()
    {
        return view('livewire.resumen-planilla-detalle-component');
    }
}

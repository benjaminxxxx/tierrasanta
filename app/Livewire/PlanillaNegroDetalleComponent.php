<?php

namespace App\Livewire;

use App\Models\Configuracion;
use App\Models\DescuentoSP;
use App\Models\Grupo;
use App\Models\PlanillaBlanco;
use Carbon\Carbon;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;

class PlanillaNegroDetalleComponent extends Component
{
    use LivewireAlert;
    public $anio;
    public $mes;
    public $informacionBlanco;
    public $meses;
    public $mesTitulo;
    public $diasLaborables;
    public $grupoColores;
    public $diasMes;
    public $totalHoras;
    public $informacionBlancoDetalle;

    public function mount()
    {
        $this->grupoColores = Grupo::get()->pluck("color", "codigo")->toArray();
        $this->obtenerInformacionMensual();
        $this->meses = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
    }
    public function obtenerInformacionMensual()
    {
        if (!$this->mes || !$this->anio) {
            return;
        }

        $this->diasMes = Carbon::createFromDate($this->anio, $this->mes)->daysInMonth;

       

        $this->informacionBlanco = PlanillaBlanco::where('mes', $this->mes)->where('anio', $this->anio)->first();

        if ($this->informacionBlanco) {
            $this->diasLaborables = $this->informacionBlanco->dias_laborables;
            $this->totalHoras = $this->informacionBlanco->total_horas;
            $this->informacionBlancoDetalle = $this->informacionBlanco->detalle;
        }
    }
    public function render()
    {
        if ($this->mes) {
            $this->mesTitulo = $this->meses[$this->mes - 1];
        }
        return view('livewire.planilla-negro-detalle-component');
    }
}

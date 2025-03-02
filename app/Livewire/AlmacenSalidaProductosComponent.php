<?php

namespace App\Livewire;

use App\Models\RptDistribucionCombustible;
use Carbon\Carbon;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;
use Illuminate\Support\Facades\Session;

class AlmacenSalidaProductosComponent extends Component
{
    use LivewireAlert;
    public $anio;
    public $mes;
    public $destino;
    public $reporteMensualCombustible;
    protected $listeners = ['rptDistribucionesGeneradas'];
    public function mount($mes = null, $anio = null,$destino = 'productos')
    {
        $this->mes = Session::get('fecha_reporte_mes', Carbon::now()->format('m'));
        $this->anio = Session::get('fecha_reporte_anio',Carbon::now()->format('Y'));
        $this->destino = $destino;
        $this->obtenerReporte();
    }
    public function updatedMes($valor)
    {
        
        $fecha = Carbon::createFromDate($this->anio, $valor, 1);

        $this->mes = $fecha->format('m');
        $this->anio = $fecha->format('Y');
        Session::put('fecha_reporte_mes', $this->mes);
        Session::put('fecha_reporte_anio', $this->anio);
        $this->obtenerReporte();
    }
    public function updatedAnio($anio)
    {
        $fecha = Carbon::createFromDate($anio, $this->mes, 1);
        $this->mes = $fecha->format('m');
        $this->anio = $fecha->format('Y');
        Session::put('fecha_reporte_mes', $this->mes);
        Session::put('fecha_reporte_anio', $this->anio);
        $this->obtenerReporte();
    }
    public function rptDistribucionesGeneradas(){
        $this->alert('success','Reporte generado exitosamente.');
        $this->obtenerReporte();
    }
    
    public function mesAnterior()
    {
        $fecha = Carbon::createFromDate($this->anio, $this->mes, 1)->subMonth();
        $this->mes = $fecha->format('m');
        $this->anio = $fecha->format('Y');
        Session::put('fecha_reporte_mes', $this->mes);
        Session::put('fecha_reporte_anio', $this->anio);
        $this->obtenerReporte();
    }

    public function mesSiguiente()
    {
        $fecha = Carbon::createFromDate($this->anio, $this->mes, 1)->addMonth();
        $this->mes = $fecha->format('m');
        $this->anio = $fecha->format('Y');
        Session::put('fecha_reporte_mes', $this->mes);
        Session::put('fecha_reporte_anio', $this->anio);
        $this->obtenerReporte();
    }
    public function obtenerReporte(){
        $this->reporteMensualCombustible = RptDistribucionCombustible::where('mes',$this->mes)
        ->where('anio',$this->anio)
        ->first();
    }
   
    public function render()
    {        
        return view('livewire.almacen-salida-productos-component');
    }
}

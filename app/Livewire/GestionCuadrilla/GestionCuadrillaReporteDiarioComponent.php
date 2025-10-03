<?php

namespace App\Livewire\GestionCuadrilla;
use App\Livewire\Traits\ConFechaReporteDia;
use App\Livewire\Traits\ConManejarErrores;
use App\Models\Campo;
use App\Models\Labores;
use App\Services\Modulos\ReporteDiarioCuadrillaServicio;
use Illuminate\Validation\ValidationException;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;

class GestionCuadrillaReporteDiarioComponent extends Component
{
    use LivewireAlert, ConFechaReporteDia, ConManejarErrores;
    
    #region VARIABLES E INICIALIZACION
    public $trabajadores = [];
    public $totalColumnas = 0;
    public $labores = [];
    public $campos = [];
    public $tramos = [];
    public $tramoSeleccionadoId;
    protected $listeners = ['storeTableDataGuardarActividadDiaria', 'registroDetalleHorasExterno'=>'refrescarTabla'];
    protected ReporteDiarioCuadrillaServicio $reporteDiarioCuadrillaServicio;
    public function boot(ReporteDiarioCuadrillaServicio $reporteDiarioCuadrillaServicio)
    {
        $this->reporteDiarioCuadrillaServicio = $reporteDiarioCuadrillaServicio;
    }
    public function mount()
    {
        $this->inicializarFecha();
        $this->inicializarValores();
        $this->cargarDatosDeReporte();
    }
    protected function despuesFechaModificada(string $fecha)
    {
        $this->detectarTramos($fecha);
        $this->refrescarTabla($fecha);
    }
    #endregion
    
    #region METODOS
    private function detectarTramos($fecha){
        $this->tramos = $this->reporteDiarioCuadrillaServicio->obtenerTramosEnFecha($fecha);
        if($this->tramos && $this->tramos->count() == 1){
            $this->tramoSeleccionadoId = $this->tramos->first()->id;
        }
    }
    private function inicializarValores()
    {
        $this->labores = Labores::get()->pluck('codigo')->toArray();
        $this->campos = Campo::get()->pluck('nombre')->toArray();
    }
    private function cargarDatosDeReporte()
    {
        try {
            if(!$this->tramoSeleccionadoId || !$this->fecha){
                return;
            }
            $resultado = $this->reporteDiarioCuadrillaServicio->obtenerDatosParaReporteDiario($this->fecha,$this->tramoSeleccionadoId);
            $this->trabajadores = $resultado['data'];
            $this->totalColumnas = $resultado['total_columnas'];
        } catch (\Throwable $e) {
            $this->manejarError($e,'Error al cargar los datos del reporte diario');
        }
    }
    public function storeTableDataGuardarActividadDiaria($datos)
    {
        try {
            $this->reporteDiarioCuadrillaServicio->guardarReporteDiario($this->fecha, $datos);
            $this->refrescarTabla($this->fecha); 
            $this->alert('success', 'Registro actualizado correctamente');

        } catch (ValidationException $ex) {
            $this->alert('error', implode("\n", $ex->validator->errors()->all()));
        } catch (\Throwable $e) {
            $this->manejarError($e,'Error al guardar el reporte diario');
        }
    }
    public function refrescarTabla($fecha)
    {
        $this->fecha = $fecha;
        $this->cargarDatosDeReporte();
        $this->dispatch('actualizarTablaCuadrilleros', $this->trabajadores, $this->totalColumnas);
    }
    public function render()
    {
        return view('livewire.gestion-cuadrilla.gestion-cuadrilla-reporte-diario-component');
    }
    #endregion
}
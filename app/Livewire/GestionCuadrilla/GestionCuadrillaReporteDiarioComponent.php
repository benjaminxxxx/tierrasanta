<?php

namespace App\Livewire\GestionCuadrilla;
use App\Services\Cuadrilla\CuadrilleroServicio;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;
use Session;

class GestionCuadrillaReporteDiarioComponent extends Component
{
    use LivewireAlert;
    public $fecha;
    public $trabajadores = [];
    public $totalColumnas = 0;
    protected $listeners = ['storeTableDataGuardarActividadDiaria', 'cuadrilla_reporte_diario_registrado'];
    public function mount()
    {
        $this->fecha = Session::get('fecha_reporte', Carbon::now()->format('Y-m-d'));
        $resultado = CuadrilleroServicio::obtenerHandsontableReporteDiario($this->fecha);

        $this->trabajadores = $resultado['data'];
        $this->totalColumnas = $resultado['total_columnas'];
    }
  
    public function cuadrilla_reporte_diario_registrado($fecha)
    {
        $this->fecha = $fecha;
        $resultado = CuadrilleroServicio::obtenerHandsontableReporteDiario($fecha);
        $trabajadores = $resultado['data'];
        $totalColumnas = $resultado['total_columnas'];
        $this->dispatch('actualizarTablaCuadrilleros', $trabajadores, $totalColumnas);

    }
    public function storeTableDataGuardarActividadDiaria($datos)
    {
        try {
            CuadrilleroServicio::guardarDesdeHandsontable($this->fecha, $datos);
            $this->cuadrilla_reporte_diario_registrado($this->fecha);
            $this->alert('success', 'Registro actualizado correctamente');
        } catch (ValidationException $ex) {
            $this->alert('error', implode("\n", $ex->validator->errors()->all()));
        } catch (\Throwable $e) {
            $this->alert('error', $e->getMessage());
        }
    }

    public function fechaAnterior()
    {
        $this->fecha = Carbon::parse($this->fecha)->subDay()->format('Y-m-d');
        Session::put('fecha_reporte', $this->fecha);
        $this->cuadrilla_reporte_diario_registrado($this->fecha);
    }

    public function fechaPosterior()
    {
        $this->fecha = Carbon::parse($this->fecha)->addDay()->format('Y-m-d');
        Session::put('fecha_reporte', $this->fecha);
        
        $this->cuadrilla_reporte_diario_registrado($this->fecha);
    }
    public function updatedFecha($fecha)
    {
        Session::put('fecha_reporte', $fecha);
        $this->cuadrilla_reporte_diario_registrado($fecha);
    }
    public function render()
    {
        return view('livewire.gestion-cuadrilla.gestion-cuadrilla-reporte-diario-component');
    }
}
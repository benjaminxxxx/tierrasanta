<?php

namespace App\Livewire;

use App\Models\CochinillaIngreso;
use App\Models\CochinillaIngresoDetalle;
use App\Models\CochinillaObservacion;
use App\Services\CochinillaIngresoServicio;
use DB;
use Livewire\Component;
use Livewire\WithPagination;
use Session;
//MODULO COCHINILLA COSECHA MAMA
class CochinillaCosechaMamasComponent extends Component
{
    use WithPagination;
    public $campoSeleccionado;
    public $campaniaSeleccionado;
    public $observacionSeleccionado;
    public $anioSeleccionado;
    public $aniosDisponibles = [];
    public $observaciones = [];
    public $campaniaId;
    public $campaniaUnica = false;
    public function mount($campaniaId = null, $campaniaUnica = false)
    {
        CochinillaIngresoServicio::estandarizarIngresos();

        $this->anioSeleccionado = Session::get('anio_seleccionado');
        $this->observacionSeleccionado = Session::get('observacion_seleccionado');
        $this->campoSeleccionado = Session::get('campo_seleccionado');
        $this->campaniaSeleccionado = Session::get('campania_seleccionado');
        $this->campaniaId = $campaniaId;
        $this->campaniaUnica = $campaniaUnica;
        $this->observaciones = CochinillaObservacion::cosechasMama()->get();
        $this->aniosDisponibles = CochinillaIngreso::select(DB::raw('YEAR(fecha) as anio'))
            ->groupBy('anio')
            ->orderBy('anio', 'desc')
            ->pluck('anio');
    }
    public function updatedCampoSeleccionado($campo)
    {
        Session::put('campo_seleccionado',$campo);
        $this->resetPage();
    }
    public function updatedCampaniaSeleccionado($campania)
    {
        Session::put('campania_seleccionado',$campania);
        $this->resetPage();
    }
    public function updatedObservacionSeleccionado($osbervacion)
    {
        Session::put('observacion_seleccionado',$osbervacion);
        $this->resetPage();
    }
    public function updatedAnioSeleccionado($anio){
        Session::put('anio_seleccionado',$anio);
        $this->resetPage();
    }
    public function render()
    {
        $query = CochinillaIngresoDetalle::query()->with(['ingreso', 'ingreso.campoCampania']);

        // FILTROS
        if ($this->campaniaUnica && $this->campaniaId) {
            $query->whereHas('ingreso', function ($q) {
                $q->where('campo_campania_id', $this->campaniaId);
            });
        } else {
            if ($this->campoSeleccionado) {
                $query->whereHas('ingreso', function ($q) {
                    $q->where('campo', $this->campoSeleccionado);
                });
            }

            if ($this->campaniaSeleccionado) {
                $query->whereHas('ingreso.campoCampania', function ($q) {
                    $q->where('nombre_campania', $this->campaniaSeleccionado);
                });
            }
        }

        if ($this->observacionSeleccionado) {
            $query->where('observacion', $this->observacionSeleccionado);
        }

        if ($this->anioSeleccionado) {
            $query->whereYear('fecha', $this->anioSeleccionado);
        }

        return view('livewire.cochinilla-cosecha-mamas-component', [
            'cosechasMama' => $query->orderBy('fecha', 'desc')
                ->orderBy('sublote_codigo', 'asc')
                ->paginate(20),
        ]);
    }
}

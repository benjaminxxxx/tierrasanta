<?php

namespace App\Livewire;

use App\Models\CampoCampania;
use App\Models\CochinillaIngreso;
use App\Models\CochinillaObservacion;
use DB;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;
use Livewire\WithPagination;
//MODULO COCHINILLA INGRESO
class CochinillaIngresoComponent extends Component
{
    use LivewireAlert;
    use WithPagination;
    public $mostrarSubLote = false;
    public $filasSeleccionadas = [];
    public $seleccionarTodo = false;
    public $campoSeleccionado;
    public $campaniaSeleccionado;
    public $observacionSeleccionado;
    public $lote;
    public $anioSeleccionado;
    public $filtroVenteado;
    public $filtroFiltrado;
    public $aniosDisponibles = [];
    public $observaciones = [];
    protected $listeners = [
        'cochinillaIngresado',
        'detalleIngresoAgregado' => '$refresh',
        "venteadoAgregado" => '$refresh',
        "filtradoAgregado" => '$refresh'
    ];
    public function mount()
    {
        $this->observaciones = CochinillaObservacion::all();
    }

    public function cochinillaIngresado()
    {
        $this->resetPage();
    }
    public function updatedCampoSeleccionado()
    {
        $this->resetPage();
    }
    public function updatedCampaniaSeleccionado()
    {
        $this->resetPage();
    }
    public function updatedObservacionSeleccionado()
    {
        $this->resetPage();
    }
    public function updatedFiltroVenteado()
    {
        $this->resetPage();
    }
    public function updatedFiltroFiltrado()
    {
        $this->resetPage();
    }
    public function sincronizarDatos()
    {
        foreach ($this->filasSeleccionadas as $lote) {
            $ingreso = CochinillaIngreso::where("lote", $lote)->first();
            if ($ingreso) {
                $fecha = $ingreso->fecha;
                $campo = $ingreso->campo;

                //ACTUALIZAR LA CAMPAÑA
                $campania = CampoCampania::where("campo", $campo)
                    ->whereDate('fecha_inicio', '<=', $fecha)
                    ->orderBy('fecha_inicio', 'desc')
                    ->first();

                if ($campania) {
                    $ingreso->campo_campania_id = $campania->id;
                }
                //ACTUALIZAR EL AREA SI NO EXISTE
                if (!$ingreso->area) {
                    $ingreso->area = $ingreso->campoRelacionada->area;
                }

                $ingreso->save();
            }
        }

        $this->filasSeleccionadas = [];
        $this->seleccionarTodo = false;
        $this->alert('success', 'Datos sincronizados correctamente.');
    }
    public function eliminarIngreso($ingresoId)
    {
        $ingreso = CochinillaIngreso::find($ingresoId);
        if ($ingreso) {
            $ingreso->venteados()->delete();
            $ingreso->filtrados()->delete();
            $ingreso->delete();
            $this->alert('success', 'Ingreso eliminado correctamente.');
        } else {
            $this->alert('error', 'Ingreso no encontrado.');
        }

    }
    public function render()
    {
        $query = CochinillaIngreso::with(['detalles', 'campoCampania', 'detalles.observacionRelacionada', 'venteados', 'filtrados']);
        if ($this->lote) {
            $query->where('lote', $this->lote);
        }
        if ($this->filtroVenteado) {
            if ($this->filtroVenteado == 'conventeado') {
                $query->whereHas('venteados');
            }

            if ($this->filtroVenteado == 'sinventeado') {
                $query->whereDoesntHave('venteados');
            }
        }
        if ($this->filtroFiltrado) {
            if ($this->filtroFiltrado === 'confiltrado') {
                $query->whereHas('filtrados');
            }
            if ($this->filtroFiltrado === 'sinfiltrado') {
                $query->whereDoesntHave('filtrados');
            }
        }
        if ($this->campoSeleccionado) {
            $query->where('campo', $this->campoSeleccionado);
        }
        if ($this->campaniaSeleccionado) {
            $nombreCampania = $this->campaniaSeleccionado;
            $query->whereHas('campoCampania', function ($q) use ($nombreCampania) {
                $q->where('nombre_campania', $nombreCampania);
            });
        }
        if ($this->observacionSeleccionado) {
            $query->where('observacion', $this->observacionSeleccionado);
        }
        // Clonamos el query para no afectar la paginación
        $aniosQuery = (clone $query)
            ->select(DB::raw('YEAR(fecha) as anio'))
            ->groupBy(DB::raw('YEAR(fecha)'))
            ->pluck('anio')
            ->toArray();

        $this->aniosDisponibles = $aniosQuery;

        $cochinillaIngresos = $query->orderBy('lote', 'desc');
        if ($this->anioSeleccionado) {
            $query->whereYear('fecha', $this->anioSeleccionado);
        }

        $cochinillaIngresos = $query->paginate(15);
        // Ordenamos cada página de menor a mayor
        /*
        $cochinillaIngresos->setCollection(
            $cochinillaIngresos->getCollection()->sortBy('lote')->values()
        );*/

        return view('livewire.cochinilla-ingreso-component', [
            'cochinillaIngresos' => $cochinillaIngresos
        ]);
    }
}

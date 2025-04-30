<?php

namespace App\Livewire;

use App\Models\CochinillaIngreso;
use App\Models\CochinillaIngresoDetalle;
use App\Models\CochinillaObservacion;
use DB;
use Livewire\Component;
use Livewire\WithPagination;

class CochinillaCosechaMamasComponent extends Component
{
    use WithPagination;
    public $campoSeleccionado;
    public $campaniaSeleccionado;
    public $observacionSeleccionado;
    public $anioSeleccionado;
    public $aniosDisponibles = [];
    public $observaciones = [];
    public function mount()
    {
        $this->observaciones = CochinillaObservacion::cosechasMama()->get();
        $this->aniosDisponibles = CochinillaIngreso::select(DB::raw('YEAR(fecha) as anio'))
            ->groupBy('anio')
            ->orderBy('anio', 'desc')
            ->pluck('anio');
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
    public function render()
    {
        $query = CochinillaIngreso::with(['detallesMama', 'campoCampania', 'observacionRelacionada'])
            ->where(function ($q) {
                $q->whereHas('detallesMama')
                    ->orWhereHas('observacionRelacionada', function ($q2) {
                        $q2->where('es_cosecha_mama', true);
                    });
            });

        // FILTROS
        if ($this->campoSeleccionado) {
            $query->where('campo', $this->campoSeleccionado);
        }

        if ($this->campaniaSeleccionado) {
            $query->whereHas('campoCampania', function ($q) {
                $q->where('nombre_campania', $this->campaniaSeleccionado);
            });
        }

        if ($this->observacionSeleccionado) {
            // Filtro observaciones tanto del ingreso como de los detalles
            $query->where(function ($q) {
                $q->where('observacion', $this->observacionSeleccionado)
                    ->orWhereHas('detallesMama', function ($q2) {
                        $q2->where('observacion', $this->observacionSeleccionado);
                    });
            });
        }

        if ($this->anioSeleccionado) {
            $query->whereYear('fecha', $this->anioSeleccionado);
        }

        $ingresosPaginados = $query->orderBy('fecha', 'desc')->paginate(15);

        // Armamos colección para la vista
        $cosechasMama = collect();

        foreach ($ingresosPaginados as $ingreso) {
            if ($ingreso->detallesMama->isNotEmpty()) {
                foreach ($ingreso->detallesMama as $detalle) {
                    $kg_ha = ($ingreso->area && $ingreso->area != 0) 
                        ? $detalle->total_kilos / $ingreso->area 
                        : null;
        
                    $cosechasMama->push((object) [
                        'fecha' => $detalle->fecha,
                        'campo' => $ingreso->campo,
                        'area' => $ingreso->area,
                        'campania' => $ingreso->campoCampania?->nombre_campania,
                        'kg' => $detalle->total_kilos,
                        'kg_ha' => $kg_ha,
                        'observacion' => $detalle->observacionRelacionada?->descripcion,
                    ]);
                }
            } elseif ($ingreso->observacionRelacionada?->es_cosecha_mama) {
                $kg_ha = ($ingreso->area && $ingreso->area != 0) 
                    ? $ingreso->total_kilos / $ingreso->area 
                    : null;
        
                $cosechasMama->push((object) [
                    'fecha' => $ingreso->fecha,
                    'campo' => $ingreso->campo,
                    'area' => $ingreso->area,
                    'campania' => $ingreso->campoCampania?->nombre_campania,
                    'kg' => $ingreso->total_kilos,
                    'kg_ha' => $kg_ha,
                    'observacion' => $ingreso->observacionRelacionada?->descripcion,
                ]);
            }
        }
        

        return view('livewire.cochinilla-cosecha-mamas-component', [
            'cosechasMama' => $cosechasMama->sortByDesc('fecha')->values(),
            'ingresosPaginados' => $ingresosPaginados, // paginador para links()
        ]);
    }


}

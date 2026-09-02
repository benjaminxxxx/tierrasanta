<?php

namespace App\Livewire\GestionCampo;

use App\Models\Campania;
use App\Models\CampoCampania;
use App\Models\ResumenCostoDiario;
use App\Services\Campo\Costos\ConsolidarCostoManoObraServicio;
use App\Traits\HandlesAlerts;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;
use Livewire\WithPagination;

class CampoCostosComponent extends Component
{
    use LivewireAlert, WithPagination, HandlesAlerts;

    public $campanias = [];
    public $campaniaId = null;
    public $fechaInicio = null;
    public $fechaFin = null;

    public $tiposDisponibles = ['planilla', 'cuadrilla', 'maquinaria', 'fertilizante', 'pesticida', 'costo_fijo', 'riego'];
    public $tiposSeleccionados = [];
    public $filtroCampo = null;
    public $campaniasDelCampo = [];
    public $filtro = '';
    public function mount()
    {
        $this->campanias = CampoCampania::orderByDesc('fecha_inicio')->get();
        $this->tiposSeleccionados = $this->tiposDisponibles; // todos visibles por defecto
    }

    public function updatedFiltroCampo($valor)
    {
        $this->campaniaId = null;
        $this->fechaInicio = null;
        $this->fechaFin = null;

        $this->campaniasDelCampo = $valor
            ? CampoCampania::where('campo', $valor)->orderByDesc('fecha_inicio')->get()
            : [];

        $this->resetPage();
    }

    public function updatedCampaniaId($valor)
    {
        if ($valor) {
            $campania = collect($this->campaniasDelCampo)->firstWhere('id', (int) $valor);

            if ($campania) {
                // Normalizamos el objeto a array si viene como Eloquent Model o stdClass
                $campania = (object) $campania;

                $this->fechaInicio = $campania->fecha_inicio instanceof \DateTimeInterface
                    ? $campania->fecha_inicio->format('Y-m-d')
                    : ($campania->fecha_inicio ? date('Y-m-d', strtotime($campania->fecha_inicio)) : null);

                $this->fechaFin = $campania->fecha_fin instanceof \DateTimeInterface
                    ? $campania->fecha_fin->format('Y-m-d')
                    : ($campania->fecha_fin ? date('Y-m-d', strtotime($campania->fecha_fin)) : null);
            }
        } else {
            // "TODAS LAS TEMPORADAS": se limpia el rango para que el usuario
            // pueda acotar manualmente si quiere, o dejarlo abierto
            $this->fechaInicio = null;
            $this->fechaFin = null;
        }

        $this->resetPage();
    }

    public function updatedTiposSeleccionados()
    {
        $this->resetPage();
    }

    public function consolidar()
    {
        try {
            if (!$this->campaniaId) {
                throw new \Exception("Selecciona una campaña antes de consolidar.");
            }

            $campania = CampoCampania::findOrFail($this->campaniaId);
            $total = app(ConsolidarCostoManoObraServicio::class)->consolidarPlanilla($campania);

            $this->alert('success', "Se consolidaron {$total} registro(s) de planilla/riego.");
        } catch (\Throwable $th) {
            $this->errorAlert($th);
        }
    }
public function aplicarFiltro()
{
    $this->resetPage();
}
    public function render()
    {
        $query = ResumenCostoDiario::query();
        if ($this->filtro) {
    $texto = trim($this->filtro);
    $query->where(function ($q) use ($texto) {
        $q->where('trabajador', 'like', "%{$texto}%")
          ->orWhere('labor_nombre', 'like', "%{$texto}%")
          ->orWhere('labor', 'like', "%{$texto}%");
    });
}

        if ($this->filtroCampo) {
            
            $query->where('campo', $this->filtroCampo);
        }

        if ($this->campaniaId) {
            $query->where('campania', CampoCampania::find($this->campaniaId)?->nombre_campania);
        }

        if ($this->fechaInicio && $this->fechaFin) {
            $query->whereBetween('fecha', [$this->fechaInicio, $this->fechaFin]);
        }

        if (!empty($this->tiposSeleccionados) && count($this->tiposSeleccionados) < count($this->tiposDisponibles)) {
            $query->whereIn('origen_tipo', $this->tiposSeleccionados);
        }

        $resumenes = $query->orderBy('fecha')->orderBy('origen_tipo')->paginate(25);

        return view('livewire.gestion-campo.campo-costos-component', compact('resumenes'));
    }
}
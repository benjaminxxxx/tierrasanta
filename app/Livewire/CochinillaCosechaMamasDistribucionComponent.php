<?php

namespace App\Livewire;

use App\Models\CochinillaInfestacion;
use App\Models\CochinillaIngresoDetalle;
use Illuminate\Support\Carbon;
use Livewire\Component;

class CochinillaCosechaMamasDistribucionComponent extends Component
{
    public $mostrarFormulario = false;
    public $cosecha;
    public $infestaciones;
    public $diasPosterioresCosecha = 10;
    protected $listeners = ['verDistribucion'];
    public function verDistribucion($cosechaId)
    {

        $this->cosecha = CochinillaIngresoDetalle::find($cosechaId);
        if ($this->cosecha) {

            $this->obtenerInfestaciones();
            $this->mostrarFormulario = true;
        }
    }
    public function obtenerInfestaciones()
    {
        if (!$this->cosecha) {
            return;
        }

        $campo = $this->cosecha->ingreso->campo;
        $fechaCosecha = Carbon::parse($this->cosecha->fecha);
        $fechaLimite = $fechaCosecha->copy()->addDays((int)$this->diasPosterioresCosecha);

        $this->infestaciones = CochinillaInfestacion::where('campo_origen_nombre', $campo)
            ->whereBetween('fecha', [$fechaCosecha->toDateString(), $fechaLimite->toDateString()])
            ->get();
    }
    public function updatedDiasPosterioresCosecha()
    {
        $this->obtenerInfestaciones();
    }
    public function render()
    {
        return view('livewire.cochinilla-cosecha-mamas-distribucion-component');
    }
}

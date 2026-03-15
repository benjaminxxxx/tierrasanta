<?php

namespace App\Livewire\GestionCochinilla;

use App\Models\CochinillaInfestacion;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;

class CochinillaInfestacionResumenGeneralComponent extends Component
{
    use LivewireAlert;
    public $sinCampania = 0;
    public $kgTemporada = 0;
    public $sinOrigen = 0;
    public $camposActivos = 0;
    public function mount()
    {
        $this->cargar();
    }
    public function cargar(): void
    {
        $this->sinCampania = CochinillaInfestacion::whereNull('campo_campania_id')->count();
        $this->sinOrigen = CochinillaInfestacion::whereNull('campo_origen_nombre')
            ->orWhere('campo_origen_nombre', '')->count();

        // Temporada activa (campaña sin fecha_fin)
        $this->kgTemporada = CochinillaInfestacion::whereHas('campoCampania', fn($q) => $q->whereNull('fecha_fin'))
            ->sum('kg_madres');
        $this->camposActivos = CochinillaInfestacion::whereHas('campoCampania', fn($q) => $q->whereNull('fecha_fin'))
            ->distinct('campo_nombre')->count('campo_nombre');
    }
    public function render()
    {
        return view('livewire.gestion-cochinilla.cochinilla-infestacion-resumen-general-component');
    }
}

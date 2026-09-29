<?php

namespace App\Livewire;

use App\Models\AlmacenProductoSalida;
use App\Models\CampoCampania;
use App\Models\InsResFertilizanteCampania;
use App\Services\AlmacenServicio;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;

class HistorialConsumoXCampaniaComponent extends Component
{
    use LivewireAlert;
    public $resumenSalidas = [];
    public $campania;
    public $salidas = [];
    public function mount($campaniaId)
    {
        $this->campania = CampoCampania::find($campaniaId);
        if ($this->campania) {
            $this->listarSalidas();
        }

    }
    public function listarSalidas()
    {
        if (!$this->campania) {
            return;
        }
        $this->salidas = InsResFertilizanteCampania::where('campo_campania_id', $this->campania->id)->get();
        $this->resumenSalidas = AlmacenServicio::generarResumenFertilizantePorPeriodo($this->campania->id);
        $this->campania->refresh();
    }
    public function sincronizarDesdeKardex()
    {
        try {
            if (!$this->campania) {
                return;
            }
            AlmacenServicio::generarFertilizantesXCampania($this->campania->id);
            $this->listarSalidas();
            $this->alert('success', 'Datos actualizados desde Kardex satisfactoriamente.');
        } catch (\Throwable $th) {
            $this->alert('error', $th->getMessage());
        }
    }
    public function render()
    {
        return view('livewire.historial-consumo-x-campania-component');
    }
}

<?php

namespace App\Livewire;

use App\Models\CampoCampania;
use App\Models\PesticidaCampania;
use App\Services\AlmacenServicio;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;

class PesticidasPorCampaniaComponent extends Component
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
        $this->salidas = PesticidaCampania::where('campo_campania_id', $this->campania->id)->get();
        $this->resumenSalidas = AlmacenServicio::generarResumenPesticidaPorPeriodo($this->campania->id);
        $this->campania->refresh();
    }
    public function sincronizarPesticidaDesdeKardex(){
        try {
            if (!$this->campania) {
                return;
            }
            AlmacenServicio::generarPesticidasXCampania($this->campania->id);
            $this->listarSalidas();
            $this->alert('success', 'Datos actualizados desde Kardex satisfactoriamente.');
        } catch (\Throwable $th) {
            $this->alert('error', $th->getMessage());
        }
    }
    public function render()
    {
        return view('livewire.pesticidas-por-campania-component');
    }
}

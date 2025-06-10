<?php

namespace App\Livewire;

use App\Models\Kardex;
use App\Models\KardexConsolidado;
use App\Services\KardexServicio;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;

class KardexIndiceComponent extends Component
{
    use LivewireAlert;
    public $kardexId;
    public $kardexConsolidado = [];
    public $verBlanco = false;
    public function mount($kardexId, $verBlanco = false)
    {
        $this->kardexId = $kardexId;
        $this->verBlanco = $verBlanco;
        $this->listarKardexConsolidado();
    }
    public function procesarKardexConsolidado()
    {
        try {
            KardexServicio::procesarKardexConsolidado($this->kardexId, $this->verBlanco);
            $this->listarKardexConsolidado();
            $this->alert('success', 'Datos procesados correctamente.');
        } catch (\Throwable $e) {
            logger()->error("Error al procesar kardex consolidado: " . $e->getMessage());
            $this->alert('error', 'Ocurrió un error al procesar el kardex.'. $e->getMessage());
        }
    }
    public function listarKardexConsolidado()
    {
        try {
            $this->kardexConsolidado = KardexServicio::listarKardexConsolidado($this->kardexId, $this->verBlanco);
        } catch (\Throwable $e) {
            // Loguear o mostrar error
            logger()->error('Error al listar kardex consolidado: ' . $e->getMessage());
            $this->alert('error', 'Error al cargar el kardex consolidado.');
        }
    }
    public function render()
    {
        return view('livewire.kardex-indice-component');
    }
}

<?php

namespace App\Livewire\Cochinilla;

use App\Models\CochinillaIngreso;
use App\Services\Cochinilla\FiltradoServicio;
use Illuminate\Support\Str;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;

class CochinillaFiltradoFormComponent extends Component
{
    use LivewireAlert, \App\Traits\HandlesAlerts;
    public $mostrarFormulario = false;
    public $cochinillaIngreso;
    public $idTable;
    /** Al cambiar o borrar lo ya registrado: queda en la auditoría */
    public string $motivo = '';
    protected $listeners = ["agregarFiltrado", "storeTableDataCochinillaIngresoFiltrado"];
    public function mount()
    {
        $this->idTable = 'table_' . Str::random(10);

    }
    public function storeTableDataCochinillaIngresoFiltrado($datos)
    {
        try {
            if (!is_array($datos)) {
                throw new \Exception("Datos inválidos para el registro de filtrado.");
            }
            $servicio = app(FiltradoServicio::class);
            $loteManual = $this->cochinillaIngreso ? $this->cochinillaIngreso->lote : null;
            $ingresoId = $this->cochinillaIngreso ? $this->cochinillaIngreso->id : null;
            $conteo = $servicio->registrarFiltrados($datos, $loteManual,$ingresoId, $this->motivo);
            $this->alert('success', $conteo ? 'Registro exitoso. Se procesaron ' . $conteo . ' filas.' : 'Filtrado del lote eliminado. Quedó en la auditoría con tu nombre y el motivo.');
            if ($this->cochinillaIngreso) {
                $this->agregarFiltrado($this->cochinillaIngreso->id);
            } else {
                $this->mostrarFormulario = false;
            }
            $this->dispatch('filtradoAgregado');

        } catch (\Throwable $e) {
            // El servicio lanzará excepciones si algo falla en la transacción
            $this->errorAlert($e, 'Error al guardar');
        }
    }
    public function agregarFiltrado($ingresoId = null)
    {
        $this->motivo = '';
        $this->resetErrorBag();
        if ($ingresoId) {
            $this->cochinillaIngreso = CochinillaIngreso::find($ingresoId);
            if ($this->cochinillaIngreso) {
                $filtrados = $this->cochinillaIngreso->filtrados->toArray();
                $this->dispatch('cargarDataFiltrado', $filtrados);
            }
        }else{
            $this->cochinillaIngreso = null;
            $this->dispatch('cargarDataFiltrado', []);
        }
        $this->mostrarFormulario = true;
    }
    public function render()
    {
        return view('livewire.cochinilla.cochinilla-filtrado-form-component');
    }
}

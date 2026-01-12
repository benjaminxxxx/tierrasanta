<?php

namespace App\Livewire\GestionCochinilla;

use App\Models\CochinillaFiltrado;
use App\Models\CochinillaIngreso;
use App\Services\Cochinilla\FiltradoServicio;
use App\Support\FormatoHelper;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;

class CochinillaFiltradoFormComponent extends Component
{
    use LivewireAlert;
    public $mostrarFormulario = false;
    public $cochinillaIngreso;
    public $idTable;
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
            if (empty($datos) || count($datos) == 0) {
                throw new \Exception("No hay datos para registrar.");
            }

            $servicio = app(FiltradoServicio::class);
            $loteManual = $this->cochinillaIngreso ? $this->cochinillaIngreso->lote : null;
            $conteo = $servicio->registrarFiltrados($datos, $loteManual);
            $this->alert('success', 'Registro exitoso. Se procesaron ' . $conteo . ' filas.');
            if ($this->cochinillaIngreso) {
                $this->agregarFiltrado($this->cochinillaIngreso->id);
            } else {
                $this->mostrarFormulario = false;
            }
            $this->dispatch('filtradoAgregado');

        } catch (\Throwable $e) {
            // El servicio lanzará excepciones si algo falla en la transacción
            $this->alert('error', 'Error al guardar: ' . $e->getMessage());
        }
    }
    public function agregarFiltrado($ingresoId = null)
    {
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
        return view('livewire.gestion-cochinilla.cochinilla-filtrado-form-component');
    }
}

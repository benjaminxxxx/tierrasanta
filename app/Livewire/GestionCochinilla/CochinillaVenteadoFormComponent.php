<?php

namespace App\Livewire\GestionCochinilla;

use App\Models\CochinillaIngreso;
use App\Models\CochinillaVenteado;
use App\Services\Cochinilla\VenteadoServicio;
use App\Support\FormatoHelper;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;

class CochinillaVenteadoFormComponent extends Component
{
    use LivewireAlert;
    public $mostrarFormulario = false;
    public $cochinillaIngreso;
    public $idTable;
    protected $listeners = ["agregarVenteado", "storeTableDataCochinillaIngresoVenteado"];
    public function mount()
    {
        $this->idTable = 'table_' . Str::random(10);
    }
    public function storeTableDataCochinillaIngresoVenteado($datos)
    {
        try {
            if (!is_array($datos)) {
                throw new \Exception("Datos inválidos para el registro de venteado.");
            }
            if (empty($datos) || count($datos) == 0) {
                throw new \Exception("No hay datos para registrar.");
            }

            $servicio = app(VenteadoServicio::class);
            $loteManual = $this->cochinillaIngreso ? $this->cochinillaIngreso->lote : null;
            $ingresoId = $this->cochinillaIngreso ? $this->cochinillaIngreso->id : null;
            $conteo = $servicio->registrarVenteado($datos, $loteManual,$ingresoId);
            $this->alert('success', 'Registro exitoso. Se procesaron ' . $conteo . ' filas.');
            if ($this->cochinillaIngreso) {
                $this->agregarVenteado($this->cochinillaIngreso->id);
            } else {
                $this->mostrarFormulario = false;
            }
            $this->dispatch('venteadoAgregado');

        } catch (\Throwable $e) {
            // El servicio lanzará excepciones si algo falla en la transacción
            $this->alert('error', 'Error al guardar: ' . $e->getMessage());
        }
    }
    public function agregarVenteado($ingresoId = null){
        if($ingresoId){
            $this->cochinillaIngreso = CochinillaIngreso::find($ingresoId);
            if ($this->cochinillaIngreso) {
                $venteados = $this->cochinillaIngreso->venteados->toArray();
                
                $this->dispatch('cargarDataVenteado', $venteados);
            }
        }else{
            $this->cochinillaIngreso = null;
            $this->dispatch('cargarDataVenteado', []);
        }
        $this->mostrarFormulario = true;
    }
    public function render()
    {
        return view('livewire.gestion-cochinilla.cochinilla-venteado-form-component');
    }
}

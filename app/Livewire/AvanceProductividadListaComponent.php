<?php

namespace App\Livewire;

use App\Services\ProductividadServicio;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;
use Illuminate\Support\Str;

class AvanceProductividadListaComponent extends Component
{
    use LivewireAlert;
    public $idTable;
    public $fecha;
    public $campo;
    public $labor;
    public $registroId;
    public $mostrarFormulario = false;
    public $cantidadDetalles;
    protected $listeners = ['listarRegistro', 'guardarInformacionAvanceProductividadLista'];
    public function mount()
    {
        $this->idTable = "table" . Str::random(15);
    }
    public function guardarInformacionAvanceProductividadLista($datos)
    {
        try {
            $productividadId = $this->registroId;
            $productividadServicio = new ProductividadServicio($productividadId);
            $productividadServicio->registrarCantidades($datos);
            $productividadServicio->registrarBonos();
            $this->alert('success', 'Cantidades y Bonos registrados correctamente.');
        } catch (\Throwable $th) {
            $this->alert('error', 'Ocurrió un error inesperado.');
            $this->dispatch('log', $th->getMessage());
        }

        $this->listarRegistro($this->registroId);
    }

    public function listarRegistro($productividadId)
    {
        try {
            $this->registroId = $productividadId;
            $this->mostrarFormulario = true;
            
            $productividadServicio = new ProductividadServicio($productividadId);
            $dataEmpleados = $productividadServicio->listarProductividadServicio();
            $cantidadDetalles = $productividadServicio->cantidadDetalles;            

            $this->dispatch('generarData', $cantidadDetalles, $dataEmpleados);
        } catch (\Throwable $th) {
            $this->alert('error', 'Ocurrió un error inesperado.');
            $this->dispatch('log', $th->getMessage());
        }
    }
    public function render()
    {
        return view('livewire.avance-productividad-lista-component');
    }
}

<?php

namespace App\Livewire\GestionInsumos;

use App\Models\InsCategoria;
use App\Models\InsKardexReporte;
use App\Models\Kardex;
use Carbon\Carbon;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;

class InsumoKardexReporteFormComponent extends Component
{
    use LivewireAlert;
    public $mostrarFormularioInsumoKardexReporte = false;
    public $anio;
    public $nombre;
    public $tipoKardex;
    public $categoriasDisponibles = [];
    public $categoriasSeleccionadas = [];
    protected $listeners = ['nuevoInsumoKardexReporte'];
    public function mount()
    {
        $this->categoriasDisponibles = InsCategoria::get()->pluck('descripcion', 'codigo')->toArray();
    }

    public function nuevoInsumoKardexReporte()
    {
        $this->resetForm();
        $this->mostrarFormularioInsumoKardexReporte = true;
    }
    public function guardarInsumoKardexReporte()
    {

        $this->validate([
            'anio' => 'required|integer|min:2000',
            'nombre' => 'required|string',
            'categoriasSeleccionadas' => 'required|array|min:1',
            'tipoKardex' => 'required|string|in:blanco,negro',
        ]);

        // Filtrar categorías seleccionadas
        $categoriasSeleccionadas = collect($this->categoriasSeleccionadas)
            ->filter(fn($v) => $v) // solo los valores verdaderos
            ->keys()               // obtener los códigos de categoría
            ->toArray();

        if (empty($categoriasSeleccionadas)) {
            $this->alert('error', 'Al menos una categoría es requerida');
            return;
        }
        try {

            $insKardexReporte = InsKardexReporte::create([
                'nombre' => $this->nombre,
                'anio' => $this->anio,
                'tipo_kardex' => $this->tipoKardex,
            ]);

            // Agregar sus categorías
            $categoriasArray = array_map(fn($codigo) => ['categoria_codigo' => $codigo], $categoriasSeleccionadas);
            $insKardexReporte->categorias()->createMany($categoriasArray);

            $this->dispatch("insumoKardexRefrescar");
            $this->resetForm();
            $this->mostrarFormularioInsumoKardexReporte = false;
            $this->alert("success", "Registro de Kardex exitoso");

        } catch (\Throwable $th) {
            $this->alert("error", $th->getMessage());
        }
    }
    public function resetForm()
    {
        $this->resetErrorBag();
        $this->reset(['anio', 'categoriasSeleccionadas','tipoKardex', 'nombre']);
    }
    public function render()
    {
        return view('livewire.gestion-insumos.insumo-kardex-reporte-form-component');
    }
}

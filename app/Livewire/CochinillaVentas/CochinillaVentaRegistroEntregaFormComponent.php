<?php

namespace App\Livewire\CochinillaVentas;

use App\Models\VentaCochinilla;
use App\Services\Cochinilla\CochinillaServicio;
use App\Services\Cochinilla\VentaServicio;
use Illuminate\Support\Carbon;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;
use Log;
use Str;

class CochinillaVentaRegistroEntregaFormComponent extends Component
{
    use LivewireAlert;
    public $cosechaSeleccionada = false;
    public $filtroOrigen = 'ingreso';
    public $ultimosIngresos = [];
    public $mostrarFormulario = false;
    public $idTable;
    public $fecha_venta;
    public $tipo_ingreso;
    public $condicionSugerencia = [];
    public $clienteSugerencia = [];
    public $itemSugerencia = [];
    public $registroEntregaGrupoId;
    public $editable = true;

    public ?int $ventaId = null; // Si es edición

    protected $listeners = ['crearRegistroVentaCochinilla', 'storeTableDataCochinillaEntregaVenta', 'editarRegistroEntrega'];
    public function mount()
    {
        $this->condicionSugerencia = VentaCochinilla::query()
            ->select('condicion')
            ->distinct()
            ->whereNotNull('condicion')
            ->pluck('condicion')
            ->toArray();

        $this->clienteSugerencia = VentaCochinilla::query()
            ->select('cliente')
            ->distinct()
            ->whereNotNull('cliente')
            ->pluck('cliente')
            ->toArray();

        $this->itemSugerencia = VentaCochinilla::query()
            ->select('item')
            ->distinct()
            ->whereNotNull('item')
            ->pluck('item')
            ->toArray();


        $this->idTable = "table" . Str::random(15);
        $this->fecha_venta = now();
        $this->tipo_ingreso = 'filtrados';
    }
    public function storeTableDataCochinillaEntregaVenta($datos)
    {
        try {
            $grupo = $this->registroEntregaGrupoId;
            $fechaVenta = $this->fecha_venta;

            $cantidadInsertada = VentaServicio::registrarEntrega(
                datos: $datos,
                grupoExistente: $grupo,
                fechaReferencia: $fechaVenta
            );
            $this->dispatch('registroEntregaVentaExitoso');
            $this->mostrarFormulario = false;
        } catch (\Throwable $th) {
            $this->alert($th->getMessage());
        }
    }
    public function buscarYCargarTablaFuente()
    {
        $this->buscarCochinilla();
        $data = [
            'ingresos' => $this->ultimosIngresos,
            'fecha_venta' => $this->fecha_venta,
        ];
        $this->dispatch('cargarTablaFuente', $data);
    }
    public function editarRegistroEntrega($grupoVenta, $editable = true)
    {
        $this->editable = $editable;
        $this->registroEntregaGrupoId = $grupoVenta;
        $ventasRealizadas = CochinillaServicio::obtenerInformacionDeVentaPorGrupo($this->registroEntregaGrupoId);
        $data = [
            'ingresos' => $ventasRealizadas,
            'fecha_venta' => $this->fecha_venta,
        ];
        $this->dispatch('cargarTablaFuente', $data);

        $this->mostrarFormulario = true;
    }
    public function crearRegistroVentaCochinilla()
    {
        $this->editable = true;
        $this->fecha_venta = Carbon::now()->format('Y-m-d');
        $this->buscarYCargarTablaFuente();
        $this->mostrarFormulario = true;
    }
    public function updatedFechaVenta()
    {
        $this->buscarCochinilla();
    }

    public function buscarCochinilla()
    {
        try {
            $this->cosechaSeleccionada = false;
            $fecha = $this->fecha_venta ?? now();
            $this->ultimosIngresos = CochinillaServicio::IngresoCochinillaParaVenta($fecha, $this->tipo_ingreso);
        } catch (\Throwable $th) {
            Log::error("Error al buscar ingresos: " . $th->getMessage(), ['file' => $th->getFile(), 'line' => $th->getLine()]);
            $this->alert('error', $th->getMessage());
        }
    }

    public function render()
    {
        return view('livewire.cochinilla_ventas.registro-form-component');
    }
}

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
    public $filtroVenteado;
    public $filtroFiltrado;
    public $ultimosIngresos = [];
    public $mostrarBuscador = false;
    public $mostrarFormulario = false;
    public $idTable;
    public $fecha_venta;
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
        $this->filtroFiltrado = null;//'confiltrado';
        $this->fecha_venta = now();
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
    public function editarRegistroEntrega($grupoVenta,$editable = true)
    {
        $this->editable = $editable;
        $this->registroEntregaGrupoId = $grupoVenta;
        $cochinillaVenta = VentaCochinilla::where('grupo_venta', $grupoVenta)->get();
        
        if ($cochinillaVenta->count() > 0) {
            $this->fecha_venta = $cochinillaVenta->first()->fecha_venta;
            $this->buscarYCargarTablaFuente();
            $data = [
                'ventas' => $cochinillaVenta,
            ];
            $this->dispatch('regenerarTabla', $data);
            $this->mostrarFormulario = true;
        } else {
            $this->alert('error', 'No se encontro registro');
        }
    }
    public function crearRegistroVentaCochinilla()
    {
        $this->editable = true;
        $this->fecha_venta = Carbon::now()->format('Y-m-d');
        $this->buscarYCargarTablaFuente();
        $data = [
            'ventas' => [],
        ];
        $this->dispatch('regenerarTabla', $data);
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

            if ($this->filtroOrigen == 'ingreso') {
                $this->ultimosIngresos = CochinillaServicio::ultimosIngresos([
                    'filtroVenteado' => $this->filtroVenteado,
                    'filtroFiltrado' => $this->filtroFiltrado,
                    'fecha' => $this->fecha_venta ?? now(),
                    'tolerancia' => 7,
                ])

                    ->get()->map(function ($ultimoIngreso) {
                        return [
                            'ingreso_id' => $ultimoIngreso->id,
                            'campo' => $ultimoIngreso->campo,
                            'fecha_ingreso' => $ultimoIngreso->fecha,
                            'fecha_filtrado' => $ultimoIngreso->fecha_proceso_filtrado,
                            'cantidad_fresca' => $ultimoIngreso->total_kilos,
                            'cantidad_seca' => $ultimoIngreso->filtrado123,
                            'uso_infestacion' => $ultimoIngreso->uso_infestaciones ? 'Si' : 'No',
                            'procedencia' => $ultimoIngreso->uso_infestaciones ? 'infestadores' : 'cosecha',
                        ];
                    })->toArray();

            } else {

            }

        } catch (\Throwable $th) {
            Log::error("Error al buscar ingresos: " . $th->getMessage(), ['file' => $th->getFile(), 'line' => $th->getLine()]);
            $this->alert('error', 'Ocurrió un error al obtener los últimos ingresos.');
        }
        $this->mostrarBuscador = true;
    }

    public function render()
    {
        return view('livewire.cochinilla_ventas.registro-form-component');
    }
}

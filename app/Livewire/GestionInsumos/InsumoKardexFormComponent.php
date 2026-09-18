<?php

namespace App\Livewire\GestionInsumos;

use App\Models\InsKardex;
use App\Models\Producto;
use App\Models\SunatTabla10TipoComprobantePago;
use App\Services\Almacen\InsumoKardexServicio;
use App\Services\KardexServicio;
use App\Services\ProductoServicio;
use App\Traits\HandlesAlerts;
use Livewire\Component;
use Livewire\Attributes\On;
use Jantinnerezo\LivewireAlert\LivewireAlert;

class InsumoKardexFormComponent extends Component
{
    use LivewireAlert, HandlesAlerts;

    public $mostrarFormularioKardex = false;
    public $insumoKardexId;
    // ARRAY COMPUESTO
    public $kardex = [
        'producto_id' => null,
        'descripcion' => '',
        'codigo_existencia' => '',
        'anio' => '',
        'tipo' => '',
        'stock_inicial' => '',
        'costo_unitario' => '',
        'costo_total' => '',
        'tipo_compra_codigo_inicial' => '',
        'tiene_saldo_inicial',
        'serie_inicial' => '',
        'numero_inicial' => '',
    ];

    public $productos = [];
    public $tabla10TipoComprobantePago = [];

    #[On('nuevoInsumoKardex')]
    public function nuevoInsumoKardex($productoId = null)
    {

        $this->insumoKardexId = null;
        $this->resetForm();
        $this->kardex['producto_id'] = $productoId;
        $this->dispatch('resetearCalculos');
        $this->mostrarFormularioKardex = true;
    }
    public function getProductos(string $search): array
    {
        return app(ProductoServicio::class)->buscar($search);
    }

    public function mount()
    {
        $this->kardex = [
            'producto_id' => null,
            'codigo_existencia' => '',
            'anio' => '',
            'tipo' => '',
            'stock_inicial' => '',
            'costo_unitario' => '',
            'costo_total' => '',
            'tipo_compra_codigo_inicial' => '',
            'tiene_saldo_inicial' => false,
            'serie_inicial' => '',
            'numero_inicial' => '',
        ];
        $this->tabla10TipoComprobantePago = SunatTabla10TipoComprobantePago::all();
        /*
        $this->productos = Producto::orderBy('nombre_comercial')->get()->map(function ($producto) {
            return [
                'id' => $producto->id,
                'name' => $producto->nombre_comercial
            ];
        })
            ->toArray();*/
    }

    public function guardarKardex()
    {
        try {
            if (!($this->kardex['tiene_saldo_inicial'] ?? false)) {
                $this->kardex['stock_inicial'] = 0;
                $this->kardex['costo_total'] = 0;
            }

            $kardex = app(InsumoKardexServicio::class)->guardarInsumoKardex($this->kardex, $this->insumoKardexId);
            $this->dispatch('insumoKardexRefrescar', kardexId: $kardex->id);
            $this->mostrarFormularioKardex = false;
            $textoCreado = $this->insumoKardexId ? 'actualizado' : 'creado';
            $this->alert('success', "Kardex {$textoCreado} correctamente.");

        } catch (\Throwable $e) {
            $this->errorAlert($e);
        }
    }

    public function resetForm()
    {
        $this->resetErrorBag();

        $this->kardex = [
            'producto_id' => null,
            'codigo_existencia' => '',
            'anio' => '',
            'tipo' => '',
            'stock_inicial' => '',
            'costo_unitario' => '',
            'costo_total' => '',
            'tipo_compra_codigo_inicial' => '',
            'serie_inicial' => '',
            'numero_inicial' => '',
        ];
    }

    public function render()
    {
        return view('livewire.gestion-insumos.insumo-kardex-form-component');
    }
}

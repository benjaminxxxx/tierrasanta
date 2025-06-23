<?php

namespace App\Livewire\CochinillaVentas;

use App\Models\CochinillaIngreso;
use App\Services\Cochinilla\CochinillaServicio;
use App\Services\Cochinilla\VentaServicio;
use Illuminate\Support\Carbon;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;
use Log;

class CochinillaVentaRegistroFormComponent extends Component
{
    use LivewireAlert;
    public $cosechaSeleccionada = false;
    public $filtroVenteado;
    public $filtroFiltrado;
    public $ultimosIngresos = [];
    public $mostrarBuscador = false;
    public $mostrarFormulario = true;
    public $form = [
        'fecha_ingreso' => null,
        'fecha_filtrado' => null,
        'area' => null,
        'fecha_venta' => null,
        'nombre_comprador' => null,

        'tipo_venta' => null,
        'factura_numero' => null,
        'lote' => null,
        'kg' => null,
        'campo' => null,

        'procedencia' => null,
        'precio_venta_dolar' => null,
        'punto_acido_carminico' => null,
        'acido_carminico' => null,
        'sacos' => null,

        'cantidad_seca' => null,
        'condicion' => null,

        'ingresos_dolar' => null,
        'tipo_cambio' => null,
        'ingresos_soles' => null,
        'estado' => null,
        'observaciones' => null,
    ];

    public ?int $ventaId = null; // Si es edición

    protected $rules = [
        'form.campo' => 'required|string|max:50',
        'form.kg' => 'required|numeric|min:0',
        'form.nombre_comprador' => 'required|string|max:255',
    ];
    public function mount()
    {
        $this->filtroFiltrado = 'confiltrado';
        $this->form['fecha_venta'] = optional(CochinillaIngreso::orderBy('fecha', 'desc')->first())->fecha ?? now();
    }
    public function updatedFiltroVenteado()
    {
        $this->buscarCosechas();
    }
    public function updatedFiltroFiltrado()
    {
        $this->buscarCosechas();
    }
    public function venderDeAqui($cochinillaIngresoId)
    {
        $ingreso = collect($this->ultimosIngresos)->firstWhere('id', $cochinillaIngresoId);

        if (!$ingreso) {
            $this->alert('error', 'No se encontró el ingreso seleccionado.');
            return;
        }

        // Asignar solo los campos conocidos
        $this->form['fecha_ingreso'] = $ingreso['fecha'] ?? null;
        $this->form['area'] = $ingreso['area'] ?? null;
        $this->form['lote'] = $ingreso['lote'] ?? null;
        $this->form['campo'] = $ingreso['campo'] ?? null;
        $this->form['kg'] = $ingreso['total_kilos'] ?? null;
        $this->form['cantidad_seca'] = $ingreso['filtrado_primera'] ?? null; // o sumatoria si deseas
        $this->cosechaSeleccionada = true;

        // Mostrar formulario
        $this->mostrarBuscador = false;
    }
    public function buscarCosechas()
    {
        try {
            $this->cosechaSeleccionada = false;
            $this->ultimosIngresos = CochinillaServicio::ultimosIngresos([
                'filtroVenteado' => $this->filtroVenteado,
                'filtroFiltrado' => $this->filtroFiltrado,
                'fecha' => $this->form['fecha_venta'] ?? now(), // o any fecha base
                'tolerancia' => 7, // o permitir configurar
            ])->get(); // Aquí ya obtienes la colección

        } catch (\Throwable $th) {
            Log::error("Error al buscar ingresos: " . $th->getMessage(), ['file' => $th->getFile(), 'line' => $th->getLine()]);
            //$this->alert('error', 'Ocurrió un error al obtener los últimos ingresos.');
            $this->alert('error', $th->getMessage());
        }
        $this->mostrarBuscador = true;
    }
    public function guardarTransaccion()
    {
        $this->validate();

        try {

            $venta = VentaServicio::guardar($this->form, $this->ventaId);

            $this->reset(['form', 'ventaId', 'mostrarFormulario']);
            $this->alert('success', $this->ventaId ? 'Venta actualizada correctamente.' : 'Venta registrada con éxito.');

        } catch (\Throwable $th) {
            Log::error('Error al guardar transacción de cochinilla: ' . $th->getMessage(), [
                'line' => $th->getLine(),
                'file' => $th->getFile(),
                'data' => $this->form,
                'ventaId' => $this->ventaId,
            ]);
            $this->alert('error', $th->getMessage());
            ;
            //$this->alert('error', 'Ocurrió un error al registrar la venta.');
        }
    }
    public function render()
    {
        return view('livewire.cochinilla_ventas.registro-form-component');
    }
}

<?php

namespace App\Livewire;

use App\Models\CostoFdmMensual;
use App\Models\CostoManoIndirecta;
use App\Services\CostoFdmServicio;
use Illuminate\Support\Carbon;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;
use Illuminate\Support\Str;

class FdmCostosComponent extends Component
{
    use LivewireAlert;
    public $idTable;
    public $mes, $anio;
    public $costosAdicionalesMensuales;
    public $blancoCostosAdicionales;
    public $negroCostosAdicionales;
    protected $listeners = ['storeTableDataCosto'];
    public function mount()
    {
        $this->idTable = "table" . Str::random(15);
        $this->costosAdicionalesMensuales = CostoFdmMensual::whereMonth('fecha', $this->mes)
            ->whereYear('fecha', $this->anio)
            ->get()
            ->toArray();

        $this->obtenerCostos();

    }
    public function obtenerCostos()
    {
        $costoManoIndirecta = CostoManoIndirecta::where('anio', $this->anio)->where('mes', $this->mes)->first();
        if ($costoManoIndirecta) {
            $this->blancoCostosAdicionales = $costoManoIndirecta->blanco_costos_adicionales_monto;
            $this->negroCostosAdicionales = $costoManoIndirecta->negro_costos_adicionales_monto;
        }
    }
    public function storeTableDataCosto($datos)
    {
        try {
            // Filtrar los datos para eliminar filas donde todos los valores relevantes sean null
            $datosFiltrados = array_filter($datos, function ($dato) {
                return is_array($dato) && !(
                    empty($dato['destinatario']) &&
                    empty($dato['descripcion']) &&
                    empty($dato['fecha'])
                );
            });
        
            // Si después de filtrar no queda nada, lanzar una alerta y salir
            if (empty($datosFiltrados)) {
                $this->alert('warning', 'No se encontraron datos válidos para guardar.');
                return;
            }
        
            // Asignar valores predeterminados a cada elemento del array
            foreach ($datosFiltrados as &$dato) {
                $dato['monto_blanco'] = $dato['monto_blanco'] ?? 0;
                $dato['monto_negro'] = $dato['monto_negro'] ?? 0;
            }
            unset($dato); // Evitar problemas con la referencia en foreach
        
            // Guardar los datos filtrados
            $costosAdicionales = CostoFdmServicio::guardar($this->mes, $this->anio, $datosFiltrados);
            $this->blancoCostosAdicionales = $costosAdicionales['costo_adicional_blanco'];
            $this->negroCostosAdicionales = $costosAdicionales['costo_adicional_negro'];
        
            $this->alert('success', 'Costos guardados correctamente.');
        } catch (\Exception $e) {
            $this->alert('error', 'Error al guardar los costos: ' . $e->getMessage());
        }
        
    }


    public function render()
    {
        return view('livewire.fdm-costos-component');
    }
}

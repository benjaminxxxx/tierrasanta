<?php

namespace App\Livewire;

use App\Models\CostoFdmMensual;
use App\Models\CostoManoIndirecta;
use App\Models\ParametroMensual;
use App\Services\CostoFdmServicio;
use App\Services\FDM\CostoServicio;
use App\Services\FDM\MaquinariaFdmServicio;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;
use Illuminate\Support\Str;

class FdmCostosComponent extends Component
{
    use LivewireAlert;
    public $idTable;
    public $mes;
    public $anio;
    public $costosAdicionalesMensuales;
    public $blancoCostosAdicionales;
    public $negroCostosAdicionales;
    public $negro_planillero_monto;
    public $costoManoIndirecta;
    public $parametros = [];
    protected $listeners = ['storeTableDataCosto'];
    public function mount()
    {
        $this->idTable = "table" . Str::random(15);
        $this->costosAdicionalesMensuales = CostoFdmMensual::whereMonth('fecha', $this->mes)
            ->whereYear('fecha', $this->anio)
            ->get()
            ->toArray();

        $this->obtenerCostos();

        $this->obtenerParametros();

    }
    public function obtenerParametros()
    {
        $this->parametros = ParametroMensual::obtenerMes($this->mes, $this->anio, [
            'combustible_fdm_monto_blanco',
            'combustible_fdm_monto_negro',
            'adicionales_fdm_monto_blanco',
            'adicionales_fdm_monto_negro',
            'combustible_fdm_paso1',
            'combustible_fdm_paso2',
            'combustible_fdm_paso3',
        ]);

        $this->blancoCostosAdicionales = $this->parametros['adicionales_fdm_monto_blanco']->valor ?? '-';
        $this->negroCostosAdicionales = $this->parametros['adicionales_fdm_monto_negro']->valor ?? '-';
    }
    public function obtenerCostos()
    {
        $costoManoIndirecta = CostoManoIndirecta::where('anio', $this->anio)->where('mes', $this->mes)->first();
        $this->costoManoIndirecta = $costoManoIndirecta;
        if ($costoManoIndirecta) {

            $this->blancoCostosAdicionales = $costoManoIndirecta->blanco_costos_adicionales_monto;
            $this->negroCostosAdicionales = $costoManoIndirecta->negro_costos_adicionales_monto;
            $this->negro_planillero_monto = $costoManoIndirecta->negro_planillero_monto;
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
            //$this->blancoCostosAdicionales = $costosAdicionales['costo_adicional_blanco'];
            //$this->negroCostosAdicionales = $costosAdicionales['costo_adicional_negro'];
            $this->obtenerParametros();
            $this->alert('success', 'Costos guardados correctamente.');
        } catch (\Exception $e) {
            $this->alert('error', 'Error al guardar los costos: ' . $e->getMessage());
        }

    }
    public function recalcularMaquinaria()
    {
        try {
            $maquinariaServicio = new MaquinariaFdmServicio($this->mes, $this->anio);
            $maquinariaServicio->calcularCosto();
            $this->alert('success', 'Costos de Maquinaria calculados correctamente.');
        } catch (\Throwable $th) {
            $this->alert('error', $th->getMessage());
        }

        $this->obtenerParametros();
    }
    public function recalcularCostoFdm($tipoCosto)
    {
        try {
            switch ($tipoCosto) {
                case 'cuadrilleros':
                    CostoServicio::calcularCostoCuadrillaFDM($this->mes, $this->anio);
                    break;

                case 'planilleros':
                    CostoServicio::calcularCostoPlanillaFDM($this->mes, $this->anio);
                    break;

                case 'maquinarias':
                    CostoServicio::calcularCostoMaquinariaFDM($this->mes, $this->anio);
                    break;

                case 'maquinarias_salida':
                    CostoServicio::calcularCostoMaquinariaSalidaFDM($this->mes, $this->anio);
                    break;

                case 'costos_adicionales':
                    CostoServicio::calcularCostoAdicionalFDM($this->mes, $this->anio);
                    break;

                case 'todo':
                    // Opcional: si implementaste `recalcularTodoFDM`
                    CostoServicio::recalcularTodoFDM($this->mes, $this->anio);
                    break;

                default:
                    $this->alert('error', "Tipo de costo no reconocido: $tipoCosto");
                    return;
            }

            $this->obtenerCostos();
            $this->alert('success', 'Datos procesados correctamente');
        } catch (\Throwable $th) {
            $this->alert('error', $th->getMessage());
        }
    }


    public function render()
    {
        return view('livewire.fdm-costos-component');
    }
}

<?php

namespace App\Livewire;

use App\Models\CochinillaIngreso;
use App\Models\Campo;
use App\Models\CampoCampania;
use App\Models\CochinillaIngresoDetalle;
use App\Models\CochinillaObservacion;
use App\Models\Observacion;
use App\Models\Siembra;
use App\Services\CochinillaIngresoServicio;
use Illuminate\Support\Carbon;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;
//MODULO COCHINILLA INGRESO
class CochinillaIngresoFormComponent extends Component
{
    use LivewireAlert;
    public $mostrarFormulario = false;
    public $cochinillaIngresoDetalleId;

    public $lote;
    public $fecha;
    public $campoSeleccionado;
    public $observacionSeleccionada;
    public $area;
    public $observaciones;
    public $kg_total;
    public $detalleModal = false;
    public $campania;
    public $campos = [];
    public $fechaSiembra;

    protected $listeners = ['agregarIngreso', 'editarIngresoDetalle'];
    public function mount()
    {
        $this->campos = Campo::listar();
        $this->observaciones = CochinillaObservacion::all();
    }
    public function editarIngresoDetalle($ingresoDetalleId)
    {
        $ingresoDetalle = CochinillaIngresoDetalle::find($ingresoDetalleId);
        if ($ingresoDetalle) {
            $this->resetForm();
            $this->cochinillaIngresoDetalleId = $ingresoDetalle->id;
            $this->campania = $ingresoDetalle->ingreso->campoCampania;
            $this->lote = $ingresoDetalle->sublote_codigo;
            $this->fecha = $ingresoDetalle->fecha;
            $this->campoSeleccionado = $ingresoDetalle->ingreso->campo;
            $this->area = $ingresoDetalle->ingreso->area;
            $this->observacionSeleccionada = $ingresoDetalle->observacion;
            $this->kg_total = $ingresoDetalle->total_kilos;

            $this->buscarSiembra();
            $this->mostrarFormulario = true;
        }
    }
    public function agregarIngreso()
    {
        // Reiniciar formulario y establecer fecha actual
        $this->resetForm();
        $this->fecha = now()->format('Y-m-d');

        // Generar el siguiente código de lote
        $this->lote = CochinillaIngresoServicio::generarCodigoSiguiente();

        // Obtener último ingreso para usar sus datos como referencia
        if ($ultimo = CochinillaIngresoServicio::obtenerUltimoIngreso()) {
            [$this->campoSeleccionado, $this->area] = [$ultimo->campo, $ultimo->area];
        }

        // Buscar datos relacionados a la siembra según el campo seleccionado
        $this->buscarSiembra();
        $this->loadCampanias();

        // Mostrar el formulario
        $this->mostrarFormulario = true;
    }

    public function resetForm()
    {
        $this->resetErrorBag();
        $this->reset([
            'cochinillaIngresoDetalleId',
            'fecha',
            'lote',
            'fechaSiembra',
            'campania',
            'campoSeleccionado',
            'observacionSeleccionada',
            'area',
            'kg_total'
        ]);
    }
    public function buscarSiembra()
    {

        $siembra = Siembra::masProximaAntesDe($this->fecha, $this->campoSeleccionado);
        if ($siembra) {
            $this->fechaSiembra = $siembra->fecha_siembra;
        } else {
            $this->fechaSiembra = null;
        }
    }
    public function updatedFecha()
    {
        $this->buscarSiembra();
        $this->loadCampanias();
    }
    public function updatedCampoSeleccionado($valorNuevoCampo)
    {
        $campo = Campo::where('nombre', $valorNuevoCampo)->first();

        if ($campo) {
            $this->area = $campo->area;
        } else {
            $this->area = null;
        }
        $this->buscarSiembra();
        $this->loadCampanias();
    }

    public function loadCampanias()
    {
        if ($this->campoSeleccionado && $this->fecha) {
            $this->campania = CampoCampania::masProximaAntesDe($this->fecha, $this->campoSeleccionado);
        } else {
            $this->campania = null;
        }
    }

    public function registrar()
    {
        $this->validate([
            'lote' => 'required|numeric',
            'campoSeleccionado' => 'required|exists:campos,nombre',
            'observacionSeleccionada' => 'required',
            'kg_total' => 'nullable|numeric'
        ]);

        try {
            if (!$this->campania) {
                return $this->alert('error', 'No hay campañas disponibles para este ingreso.');
            }

            CochinillaIngresoServicio::registrarDetalle([
                'cochinillaIngresoDetalleId' => $this->cochinillaIngresoDetalleId,
                'lote' => $this->lote,
                'fecha' => $this->fecha,
                'campo' => $this->campoSeleccionado,
                'area' => $this->area,
                'campo_campania_id' => $this->campania->id,
                'observacion' => $this->observacionSeleccionada,
                'total_kilos' => (float) $this->kg_total
            ]);

            $this->mostrarFormulario = false;
            $this->resetForm();
            //$this->cochinillaIngresoDetalleId = $ingreso->id;
            $this->dispatch('cochinillaIngresado');
            $this->alert('success', "Registro exitoso.");
        } catch (\Throwable $th) {
            $this->alert('error', $th->getMessage());
        }
    }
    public function render()
    {
        return view('livewire.cochinilla-ingreso-form-component', [
            'observaciones' => CochinillaObservacion::all(),
        ]);
    }
}

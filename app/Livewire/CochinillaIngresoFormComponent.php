<?php

namespace App\Livewire;

use App\Models\CochinillaIngreso;
use App\Models\Campo;
use App\Models\CampoCampania;
use App\Models\CochinillaObservacion;
use App\Models\Observacion;
use App\Models\Siembra;
use Illuminate\Support\Carbon;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;
//MODULO COCHINILLA INGRESO
class CochinillaIngresoFormComponent extends Component
{
    use LivewireAlert;
    public $mostrarFormulario = false;
    public $cochinillaIngresoId = null;

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

    protected $listeners = ['agregarIngreso', 'guardarDetalle' => 'actualizarKilosTotales', 'editarIngreso'];
    public function mount()
    {
        $this->campos = Campo::listar();
        $this->observaciones = CochinillaObservacion::all();
    }
    public function editarIngreso($ingresoId)
    {
        $ingreso = CochinillaIngreso::find($ingresoId);
        if ($ingreso) {
            $this->resetForm();
            $this->cochinillaIngresoId = $ingreso->id;
            $this->campania = $ingreso->campoCampania;
            $this->lote = $ingreso->lote;
            $this->fecha = $ingreso->fecha;
            $this->campoSeleccionado = $ingreso->campo;
            $this->area = $ingreso->area;
            $this->observacionSeleccionada = $ingreso->observacion;
            $this->kg_total = $ingreso->total_kilos;

            $this->buscarSiembra();
            $this->mostrarFormulario = true;
        }
    }
    public function agregarIngreso()
    {
        $this->resetForm();
        $this->fecha = Carbon::now()->format('Y-m-d');
        $ultimo = CochinillaIngreso::latest('lote')->first();
        $this->lote = $ultimo ? ((int) $ultimo->lote + 1) : 1;
        $this->buscarSiembra();
        $this->mostrarFormulario = true;
    }
    public function resetForm()
    {
        $this->resetErrorBag();
        $this->reset([
            'cochinillaIngresoId',
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

            $data = [
                'lote' => $this->lote,
                'fecha' => $this->fecha,
                'campo' => $this->campoSeleccionado,
                'area' => $this->area,
                'campo_campania_id' => $this->campania->id,
                'observacion' => $this->observacionSeleccionada,
                'total_kilos' => (float) $this->kg_total
            ];

            $ingreso = CochinillaIngreso::updateOrCreate(
                ['id' => $this->cochinillaIngresoId],
                $data
            );

            $this->mostrarFormulario = false;
            $this->resetForm();
            $this->cochinillaIngresoId = $ingreso->id;
            $this->dispatch('cochinillaIngresado');
            $this->alert('success', "Registro exitoso.");
        } catch (\Throwable $th) {
            $this->alert('error', $th->getMessage());
        }
    }

    public function abrirDetalle()
    {
        if (!$this->cochinillaIngresoId) {
            $this->registrar(); // Guardar si no se ha registrado aún
        }

        $this->detalleModal = true;
    }

    public function actualizarKilosTotales($kgTotal)
    {
        $this->kg_total = $kgTotal;

        CochinillaIngreso::where('id', $this->cochinillaIngresoId)
            ->update(['proveedor_kg_exportado' => $kgTotal]);
    }

    public function render()
    {
        return view('livewire.cochinilla-ingreso-form-component', [
            'observaciones' => CochinillaObservacion::all(),
        ]);
    }
}

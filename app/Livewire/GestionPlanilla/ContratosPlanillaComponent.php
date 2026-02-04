<?php

namespace App\Livewire\GestionPlanilla;

use App\Models\PlanContrato;
use App\Services\RecursosHumanos\Personal\ContratoServicio;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithoutUrlPagination;
use Livewire\WithPagination;
use Jantinnerezo\LivewireAlert\LivewireAlert;

class ContratosPlanillaComponent extends Component
{
    use WithPagination, LivewireAlert, WithoutUrlPagination, WithFileUploads;

    public $buscar = '';
    public $perPage = 10;
    public $mostrarInformacionContrato = false;
    public $mostrarInformacionFinalizar = false;
    public $contratoSeleccionado;
    public $contratoAFinalizar;
    public $datosCierre = [];
    public $fileContratos;
    public $filtros = [
        'buscar' => '',
        'estado' => 'activo', // Podrías dejarlo activo por defecto
        'tipo_planilla' => '',
        'cargo_codigo' => '',
        'grupo_codigo' => '',
        'fecha_desde' => '',
        'fecha_hasta' => '',
    ];
    protected $listeners = ['contratoActualizado' => 'refresh', 'confirmarEliminarContrato'];
    public function mount($uuid = null)
    {
        if ($uuid) {
            $empleado = \App\Models\PlanEmpleado::where('uuid', $uuid)->first();
            if ($empleado) {
                $this->filtros['buscar'] = $empleado->nombres;
            }
        }

    }

    public function updatedBuscar()
    {
        $this->resetPage();
    }

    public function limpiarFiltros()
    {
        $this->reset('filtros');
    }

    public function getContratosProperty()
    {
        return (new ContratoServicio())->listarContratos($this->filtros, $this->perPage);
    }

    public function renovarContrato($id)
    {
        $contrato = PlanContrato::find($id);

        if (!$contrato) {
            $this->alert('error', 'Contrato no encontrado');
            return;
        }

        $this->dispatch('renovarContrato', id: $id);
    }

    public function finalizarContrato($id)
    {
        $contrato = PlanContrato::find($id);

        if (!$contrato) {
            $this->alert('error', 'Contrato no encontrado');
            return;
        }
        $this->reset('datosCierre');
        $this->mostrarInformacionFinalizar = true;
        $this->contratoAFinalizar = $contrato;
    }

    public function confirmarFinalizarContrato()
    {
        $validatedData = $this->validate([
            'datosCierre.fecha_fin' => 'required|date',
            'datosCierre.motivo_cese_sunat' => 'required|string',
            'datosCierre.comentario_cese' => 'nullable|string|max:255',
        ], [
            'datosCierre.fecha_fin.required' => 'La fecha de cese es obligatoria.',
            'datosCierre.motivo_cese_sunat.required' => 'Debe seleccionar un motivo de cese.',
        ]);

        try {

            $servicio = new ContratoServicio();
            $servicio->finalizarContrato($this->contratoAFinalizar->id, $this->datosCierre);
            $this->alert('success', 'Contrato finalizado correctamente');
            $this->mostrarInformacionFinalizar = false;
        } catch (\Exception $e) {
            $this->alert('error', 'Error al finalizar el contrato: ' . $e->getMessage());
        }
    }

    public function eliminarContrato($id)
    {
        $contrato = PlanContrato::find($id);

        if (!$contrato) {
            $this->alert('error', 'Contrato no encontrado');
            return;
        }

        $this->confirm('¿Está seguro que desea eliminar este contrato?', [
            'onConfirmed' => 'confirmarEliminarContrato',
            'confirmButtonText' => 'Sí, eliminar',
            'cancelButtonText' => 'Cancelar',
            'data' => ['id' => $id]
        ]);
    }

    public function confirmarEliminarContrato($data)
    {
        $contrato = PlanContrato::find($data['id']);

        if (!$contrato) {
            $this->alert('error', 'Contrato no encontrado');
            return;
        }

        $contrato->update([
            'eliminado_por' => auth()->id(),
        ]);

        $contrato->delete();

        $this->alert('success', 'Contrato eliminado correctamente');
        $this->refresh();
    }
    public function verInformacion($id)
    {
        $contrato = PlanContrato::with('empleado')
            ->findOrFail($id);

        $this->contratoSeleccionado = [
            'empleado' => $contrato->empleado->nombre_completo,
            'tipo_planilla' => $contrato->tipo_planilla,
            'tipo_contrato' => $contrato->tipo_contrato,
            'fecha_inicio' => $contrato->fecha_inicio,
            'fecha_fin' => $contrato->fecha_fin,
            'fecha_fin_prueba' => $contrato->fecha_fin_prueba,
            'cargo_codigo' => $contrato->cargo_codigo,
            'grupo_codigo' => $contrato->grupo_codigo,
            'modalidad_pago' => $contrato->modalidad_pago,
            'motivo_cese_sunat' => $contrato->motivo_cese_sunat,
            'comentario_cese' => $contrato->comentario_cese,
            'estado' => $contrato->estado,
            'compensacion_vacacional' => $contrato->compensacion_vacacional,
            'plan_sp_codigo' => $contrato->plan_sp_codigo,
            'esta_jubilado' => $contrato->esta_jubilado ? 'Sí' : 'No',
        ];

        $this->mostrarInformacionContrato = true;
    }
    public function refresh()
    {
        $this->resetPage();
    }
    public function updatedFileContratos($file){
        try {
            app(ContratoServicio::class)->importarContratos($file);
            $this->fileContratos = null;
            $this->alert('success', 'Contratos importados correctamente');
        } catch (\Throwable $th) {
            $this->alert('error', $th->getMessage(), [
                'position' => 'center',
                'toast' => false,
                'timer' => null,
            ]);
        }
    }
    public function render()
    {
        return view('livewire.gestion-planilla.contratos-planilla-component', [
            'contratos' => $this->contratos,
        ]);
    }
}

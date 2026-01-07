<?php

namespace App\Livewire\GestionAsistencia;

use App\Services\PlanTipoAsistenciaServicio;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;
use Exception;

class TipoAsistenciaComponent extends Component
{
    use LivewireAlert;

    public $tipoAsistencias;
    protected $listeners = ['confirmarEliminar', 'resturar', 'nuevoRegistro' => '$refresh'];

    public function eliminarTipoAsistencia($id)
    {
        $this->confirm('¿Está seguro(a) que desea eliminar el registro?', [
            'onConfirmed' => 'confirmarEliminar',
            'data' => ['id' => $id],
        ]);
    }

    public function confirmarEliminar($data, PlanTipoAsistenciaServicio $servicio)
    {
        try {
            $servicio->eliminar($data['id']);
            $this->alert('success', '¡Tipo de asistencia eliminado con éxito!');
        } catch (Exception $e) {
            $this->alert('error', 'Hubo un error al eliminar: ' . $e->getMessage());
        }
    }

    public function preguntarRestaurar()
    {
        $this->confirm('Está a punto de restaurar los valores por defecto, ¿desea continuar?', [
            'onConfirmed' => 'resturar'
        ]);
    }

    public function resturar(PlanTipoAsistenciaServicio $servicio)
    {
        try {
            $servicio->restaurarPorDefecto();
            $this->alert("success", "Registro Restaurado con Éxito");
        } catch (Exception $e) {
            $this->alert("error", $e->getMessage());
        }
    }

    public function render(PlanTipoAsistenciaServicio $servicio)
    {
        $this->tipoAsistencias = $servicio->listarTodos();
        return view('livewire.gestion-asistencia.tipo-asistencia-component');
    }
}
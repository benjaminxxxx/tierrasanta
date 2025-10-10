<?php

namespace App\Livewire\GestionPlanilla\AdministrarPlanillero;
use App\Services\Modulos\Planilla\GestionPlanillaEmpleados;
use Exception;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;

class GestionPlanillaEmpleadosComponent extends Component
{
    use LivewireAlert;
    public $planCargoId;
    public $planDescuentoSpCodigo;
    public $planGrupoCodigo;
    public $filtro;
    public $planGenero;
    public $planEliminados;
    public $planTipoPlanilla;
    protected $listeners = ['empleadoGuardado'=>'$refresh'];
    public function ordenarPlanillaAgraria(){
        
        try {
            $empleado = app(GestionPlanillaEmpleados::class)->obtenerPlanillaAgrariaActual();
            if($empleado->count()==0){
                throw new Exception("No hay registros con contrato agrario aún");
            }
        } catch (\Throwable $th) {
            $this->alert('error',$th->getMessage());
        }
    }
    public function eliminarEmpleado($uuid){
        try {
            app(GestionPlanillaEmpleados::class)->eliminarEmpleado($uuid);
            $this->alert('success','Eliminado correctamente');
        } catch (\Throwable $th) {
            $this->alert('error',$th->getMessage());
        }
    }
    public function restaurarEmpleado($uuid){
        try {
            app(GestionPlanillaEmpleados::class)->restaurarEmpleado($uuid);
            $this->alert('success','Restaurado correctamente');
        } catch (\Throwable $th) {
            $this->alert('error',$th->getMessage());
        }
    }
    
    public function render()
    {
        $filtros = [
            'cargo_id' => $this->planCargoId,
            'descuento_sp_codigo' => $this->planDescuentoSpCodigo,
            'grupo_codigo' => $this->planGrupoCodigo,
            'filtro' => $this->filtro,
            'genero' => $this->planGenero,
            'estado' => $this->planEliminados,
            'tipo_planilla' => $this->planTipoPlanilla,
        ];

        $planEmpleados = app(GestionPlanillaEmpleados::class)->buscarEmpleado($filtros);

        return view('livewire.gestion-planilla.administrar-planillero.gestion-planilla-empleados', [
            'empleados' => $planEmpleados,
        ]);
    }
}
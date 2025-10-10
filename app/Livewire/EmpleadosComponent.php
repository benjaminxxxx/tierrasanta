<?php

namespace App\Livewire;

use App\Models\PlanCargo;
use App\Models\PlanDescuentoSP;
use App\Services\RecursosHumanos\Personal\ContratoServicio;
use App\Traits\ListasComunes\ConGrupoPlanilla;
use Carbon\Carbon;
use Exception;
use Livewire\Component;
use App\Models\PlanEmpleado;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\WithPagination;

class EmpleadosComponent extends Component
{
    use LivewireAlert, WithPagination, ConGrupoPlanilla;
    public $isFormOpen = true;
    public $empleadoCode;
    public $search = '';
    public $cargo_id;
    public $descuento_sp_id;
    public $grupo_codigo;
    public $cargos;
    public $descuentos;
    public $estado;
    public $genero;
    public $tipo_planilla;
    public $mostrarFormularioCambioSueldos = false;
    public $trabajadoresActivos = [];
    public $mesVigencia;
    public $anioVigencia;
    protected $listeners = ['EmpleadoRegistrado' => '$refresh', 'eliminacionConfirmada', 'HijoRegistrado' => '$refresh'];
    public function mount()
    {
        $this->cargos = PlanCargo::all();
        $this->descuentos = PlanDescuentoSP::all();
        $this->estado = 'activo';
        $this->mesVigencia = Carbon::now()->format('m');
        $this->anioVigencia = Carbon::now()->format('Y');
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }
    public function editar($code)
    {
        $this->dispatch('EditarEmpleado', $code);
    }
    public function asignacionFamiliar($code)
    {
        $this->dispatch('AgregarAsignacionFamiliar', $code);
    }
    public function eliminacionConfirmada()
    {
        if ($this->empleadoCode) {
            $empleado = PlanEmpleado::where('code', $this->empleadoCode);
            if ($empleado) {
                $empleado->delete();
                $this->empleadoCode = null;
            }
        }
    }
   
    public function restaurar($code)
    {
        $empleado = PlanEmpleado::where('code', $code)->first();
        if ($empleado) {
            $empleado->status = 'activo';
            $empleado->save();
        }
    }
    public function confirmarEliminacion($code)
    {
        try {
            $empleado = PlanEmpleado::where('code', $code)->firstOrFail();
            $empleado->status = 'inactivo';
            $empleado->save();
            $this->alert('success', 'Registro eliminado correctamente.');
        } catch (\Throwable $th) {
            $this->alert('error', $th->getMessage());
        }
    }
    public function moveUp($id)
    {
        $empleado = PlanEmpleado::find($id);

        if ($empleado) {
            // Verificar si el empleado tiene un valor de 'orden' NULL
            if (is_null($empleado->orden)) {
                // Asignar valores al campo 'orden' si son NULL
                $this->assignOrderValues();
            } else {
                // Mover el empleado hacia arriba si ya tiene un valor de 'orden'
                $previous = PlanEmpleado::where('orden', '<', $empleado->orden)
                    ->orderBy('orden', 'desc')
                    ->where('status', 'activo')
                    ->first();

                if ($previous) {
                    $this->swapOrder($empleado, $previous);
                }
            }
        }
    }

    public function moveDown($id)
    {
        $empleado = PlanEmpleado::find($id);

        if ($empleado) {
            // Verificar si el empleado tiene un valor de 'orden' NULL
            if (is_null($empleado->orden)) {
                // Asignar valores al campo 'orden' si son NULL
                $this->assignOrderValues();
            } else {
                // Mover el empleado hacia abajo si ya tiene un valor de 'orden'
                $next = PlanEmpleado::where('orden', '>', $empleado->orden)
                    ->orderBy('orden', 'asc')
                    ->where('status', 'activo')
                    ->first();

                if ($next) {
                    $this->swapOrder($empleado, $next);
                }
            }
        }
    }
    public function moveAt($id, $value)
    {
        $empleado = PlanEmpleado::find($id);

        if ($empleado) {
            // Asignar el valor al campo 'orden'
            $empleado->orden = $value;
            $empleado->save(); // Guardar los cambios en la base de datos
        }
    }
    private function assignOrderValues()
    {
        // Inicializar el valor de orden
        $empleados = PlanEmpleado::orderBy('id')->where('status', 'activo')->get();
        $order = 1;

        foreach ($empleados as $empleado) {
            // Solo actualizar los empleados con 'orden' NULL
            if (is_null($empleado->orden)) {
                $empleado->orden = $order++;
                $empleado->save();
            }
        }
    }

    private function swapOrder($current, $target)
    {
        $tempOrder = $current->orden;
        $current->orden = $target->orden;
        $target->orden = $tempOrder;

        $current->save();
        $target->save();

    }

    #region aumento de sueldo
    public function abrirFormCambioMasivoSueldo()
    {
        $lista = PlanEmpleado::where('status', 'activo')
            ->with(['ultimoContrato'])
            ->get()
            ->map(function ($e) {
                return [
                    'id' => $e->id,
                    'nombre' => trim("{$e->nombres} {$e->apellido_paterno} {$e->apellido_materno}"),
                    'grupo_codigo' => $e->grupo_codigo,
                    'cargo_codigo' => $e->cargo_id, // o cargo_codigo si lo tienes así
                    'tipo_planilla' => (string) $e->tipo_planilla, // "1" o "2"
                    'sueldo_actual' => optional($e->ultimoContrato)->sueldo ?? 0,
                    'nuevo_sueldo' => optional($e->ultimoContrato)->sueldo ?? 0,
                    'seleccionado' => false,
                ];
            })
            ->values()
            ->all();

        $this->mostrarFormularioCambioSueldos = true;

        // importante: dispara el evento para que Alpine cargue la lista
        $this->dispatch('ejecutarCambioSueldos', trabajadores: $lista);
    }
    public function guardarCambiosSueldos($cambios)
    {
        try {
            
            app(ContratoServicio::class)->guardarCambiosSueldos(
                $cambios,
                $this->mesVigencia,
                $this->anioVigencia
            );
            $this->alert('success', 'Sueldos modificados correctamente en su nuevo contrato.');

        } catch (\Throwable $th) {
            return $this->alert('error', $th->getMessage());
        }

    }
    #endregion
    public function render()
    {
        $query = PlanEmpleado::query();

        if (!empty($this->search)) {
            $query->where(function ($query) {
                $query->where('nombres', 'like', '%' . $this->search . '%')
                    ->orWhere('apellido_paterno', 'like', '%' . $this->search . '%')
                    ->orWhere('apellido_materno', 'like', '%' . $this->search . '%')
                    ->orWhere('documento', 'like', '%' . $this->search . '%');
            });
        }

        // Filtro por cargo
        if (!empty($this->cargo_id)) {
            $query->where('cargo_id', $this->cargo_id);
        }

        // Filtro por descuento
        if (!empty($this->descuento_sp_id)) {
            $query->where('descuento_sp_id', $this->descuento_sp_id);
        }

        // Filtro por grupo
        if (!empty($this->grupo_codigo)) {
            if ($this->grupo_codigo === 'sg') {
                $query->whereNull('grupo_codigo');
            } else {
                $query->where('grupo_codigo', $this->grupo_codigo);
            }
        }

        // Filtro por género
        if (!empty($this->genero)) {
            $query->where('genero', $this->genero);
        }

        // Filtro por estado
        if (!empty($this->estado)) {
            $query->where('status', $this->estado);
        }

        // Filtro por tipo de planilla
        if (!empty($this->tipo_planilla)) {
            $query->where('tipo_planilla', $this->tipo_planilla);
        }

        $empleados = $query->orderBy('orden')->with(['ultimoContrato'])->paginate(50);

        return view('livewire.empleados-component', [
            'empleados' => $empleados
        ]);
    }
}

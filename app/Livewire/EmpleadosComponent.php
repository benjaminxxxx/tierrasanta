<?php

namespace App\Livewire;

use App\Models\Cargo;
use App\Models\DescuentoSP;
use App\Models\Grupo;
use Livewire\Component;
use App\Models\Empleado;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\WithPagination;

class EmpleadosComponent extends Component
{
    use LivewireAlert;
    use WithPagination;
    public $isFormOpen = true;
    public $empleadoCode;
    public $search = '';
    public $cargo_id;
    public $descuento_sp_id;
    public $grupo_codigo;
    public $cargos;
    public $descuentos;
    public $grupos;
    public $estado;
    public $genero;
    protected $listeners = ['EmpleadoRegistrado' => '$refresh', 'eliminacionConfirmada', 'HijoRegistrado' => '$refresh'];
    public function mount()
    {
        $this->cargos = Cargo::all();
        $this->descuentos = DescuentoSP::all();
        $this->grupos = Grupo::all();
    }
    public function render()
    {
        $query = Empleado::query();

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

        $empleados = $query->orderBy('orden')->paginate(20);

        return view('livewire.empleados-component', [
            'empleados' => $empleados
        ]);
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
            $empleado = Empleado::where('code', $this->empleadoCode);
            if ($empleado) {
                $empleado->delete();
                $this->empleadoCode = null;
            }
        }
    }
    public function enable($code)
    {
        $empleado = Empleado::where('code', $code)->first();
        if ($empleado) {
            $empleado->status = 'activo';
            $empleado->save();
        }
    }

    public function disable($code)
    {
        $empleado = Empleado::where('code', $code)->first();
        if ($empleado) {
            $empleado->status = 'inactivo';
            $empleado->save();
        }
    }
    public function confirmarEliminacion($code)
    {
        $this->empleadoCode = $code;

        $this->alert('question', '¿Está seguro que desea eliminar al Empleado?', [
            'showConfirmButton' => true,
            'confirmButtonText' => 'Si, Eliminar',
            'onConfirmed' => 'eliminacionConfirmada',
            'showCancelButton' => true,
            'position' => 'center',
            'toast' => false,
            'timer' => null,
            'confirmButtonColor' => '#056A70', // Esto sobrescribiría la configuración global
            'cancelButtonColor' => '#2C2C2C',
        ]);
    }
    public function moveUp($id)
    {
        $empleado = Empleado::find($id);

        if ($empleado) {
            // Verificar si el empleado tiene un valor de 'orden' NULL
            if (is_null($empleado->orden)) {
                // Asignar valores al campo 'orden' si son NULL
                $this->assignOrderValues();
            } else {
                // Mover el empleado hacia arriba si ya tiene un valor de 'orden'
                $previous = Empleado::where('orden', '<', $empleado->orden)
                    ->orderBy('orden', 'desc')
                    ->first();

                if ($previous) {
                    $this->swapOrder($empleado, $previous);
                }
            }
        }
    }

    public function moveDown($id)
    {
        $empleado = Empleado::find($id);

        if ($empleado) {
            // Verificar si el empleado tiene un valor de 'orden' NULL
            if (is_null($empleado->orden)) {
                // Asignar valores al campo 'orden' si son NULL
                $this->assignOrderValues();
            } else {
                // Mover el empleado hacia abajo si ya tiene un valor de 'orden'
                $next = Empleado::where('orden', '>', $empleado->orden)
                    ->orderBy('orden', 'asc')
                    ->first();

                if ($next) {
                    $this->swapOrder($empleado, $next);
                }
            }
        }
    }
    public function moveAt($id, $value)
    {
        $empleado = Empleado::find($id);
    
        if ($empleado) {
            // Asignar el valor al campo 'orden'
            $empleado->orden = $value;
            $empleado->save(); // Guardar los cambios en la base de datos
        }
    }
    private function assignOrderValues()
    {
        // Inicializar el valor de orden
        $empleados = Empleado::orderBy('id')->get();
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

        //$this->empleados = Empleado::orderBy('orden')->get();
    }
}

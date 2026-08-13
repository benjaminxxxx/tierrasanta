<?php

namespace App\Livewire\GestionPlanilla\DerechoHabiente;

use Livewire\Component;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Illuminate\Validation\Rule;
use App\Models\PlanEmpleado;
use App\Models\DerechoHabiente;
use App\Models\EmpleadoDerechoHabiente;
use App\Domain\DerechoHabiente\DerechoHabienteService;

class DerechoHabienteFormComponent extends Component
{
    use LivewireAlert;

    public bool $show = false;
    public string $paso = 'buscar'; // buscar | panel

    // Búsqueda inicial
    public string $documentoBusqueda = '';

    // Contexto: cuando se abre desde el wizard, ya sabemos empleado + rol a vincular
    public ?int $empleadoContextoId = null;
    public ?string $rolContexto = null;

    // Datos de la persona (derecho_habiente)
    public ?int $derechoHabienteId = null;
    public string $nombres = '';
    public string $documento = '';
    public string $tipo_documento = 'DNI';
    public string $fecha_nacimiento = '';
    public string $tipo = 'hijo';
    public bool $discapacidad_severa = false;
    public bool $percibe_pension_no_contributiva = false;
    public bool $esta_estudiando = false;
    public ?string $fecha_inicio_estudios = null;
    public ?string $fecha_fin_estudios = null;

    // Vínculos activos de esta persona (con uno o más empleados)
    public array $vinculos = [];

    // Formulario para agregar un vínculo nuevo dentro del panel
    public ?int $nuevoVinculoEmpleadoId = null;
    public string $nuevoVinculoRol = 'padre';
    public int $nuevoVinculoMes;
    public int $nuevoVinculoAnio;

    protected $listeners = [
        'abrirDerechoHabienteForm' => 'abrirEdicion',
        'abrirDerechoHabienteFormNuevo' => 'abrirCreacion',
        'quitarVinculoConfirmado'
    ];

    public function mount()
    {
        $this->nuevoVinculoMes = now()->month;
        $this->nuevoVinculoAnio = now()->year;
    }

    /** Abre en modo "editar vínculo puntual", desde el botón editar de la lista general. Salta directo al panel. */
    public function abrirEdicion(int $vinculoId)
    {
        $this->resetFormulario();

        $vinculo = EmpleadoDerechoHabiente::with('derechoHabiente.vinculos.empleado')->findOrFail($vinculoId);

        $this->cargarPersona($vinculo->derechoHabiente);
        $this->paso = 'panel';
        $this->show = true;
    }

    /** Abre desde el wizard, con empleado y rol ya conocidos. Primero pregunta el DNI. */
    public function abrirCreacion(int $empleadoId, string $rol)
    {
        $this->resetFormulario();

        $this->empleadoContextoId = $empleadoId;
        $this->rolContexto = $rol;
        $this->paso = 'buscar';
        $this->show = true;
    }

    public function getEmpleados($search)
    {
        $query = PlanEmpleado::orderBy('nombres');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('nombres', 'like', "%{$search}%")
                    ->orWhere('apellido_paterno', 'like', "%{$search}%")
                    ->orWhere('apellido_materno', 'like', "%{$search}%")
                    ->orWhere('documento', 'like', "%{$search}%");
            });
        }

        return $query->limit(10)->get(['id', 'nombres', 'apellido_paterno', 'apellido_materno'])
            ->map(fn ($e) => [
                'id' => $e->id,
                'name' => trim("{$e->nombres} {$e->apellido_paterno} {$e->apellido_materno}"),
            ])
            ->toArray();
    }

    /** Paso 1 -> Paso 2: busca por documento. Si existe, carga en modo edición. Si no, arranca en blanco con ese DNI. */
    public function buscarDocumento()
    {
        $this->resetValidation();

        if (!preg_match('/^\d{8}$/', $this->documentoBusqueda)) {
            $this->addError('documentoBusqueda', 'Ingrese un documento válido de 8 dígitos.');
            return;
        }

        $persona = DerechoHabiente::with('vinculos.empleado')
            ->where('documento', $this->documentoBusqueda)
            ->first();

        if ($persona) {
            $this->cargarPersona($persona);
        } else {
            $this->nuevaPersona($this->documentoBusqueda);
        }

        $this->paso = 'panel';
    }

    public function volverABuscar()
    {
        $this->documentoBusqueda = '';
        $this->resetPersona();
        $this->paso = 'buscar';
    }

    private function cargarPersona(DerechoHabiente $persona): void
    {
        $this->derechoHabienteId = $persona->id;
        $this->nombres = $persona->nombres;
        $this->documento = $persona->documento;
        $this->tipo_documento = $persona->tipo_documento;
        $this->fecha_nacimiento = optional($persona->fecha_nacimiento)->format('Y-m-d');
        $this->tipo = $persona->tipo;
        $this->discapacidad_severa = (bool) $persona->discapacidad_severa;
        $this->percibe_pension_no_contributiva = (bool) $persona->percibe_pension_no_contributiva;
        $this->esta_estudiando = (bool) $persona->fecha_inicio_estudios;
        $this->fecha_inicio_estudios = optional($persona->fecha_inicio_estudios)->format('Y-m-d');
        $this->fecha_fin_estudios = optional($persona->fecha_fin_estudios)->format('Y-m-d');

        $this->vinculos = $persona->vinculos
            ->map(fn ($v) => [
                'id' => $v->id,
                'empleado_id' => $v->empleado_id,
                'empleado_nombre' => $v->empleado->nombreCompleto,
                'rol' => $v->rol,
                'mes_vigencia' => $v->mes_vigencia ?? now()->month,
                'anio_vigencia' => $v->anio_vigencia ?? now()->year,
            ])
            ->values()
            ->toArray();

        // Si venimos con contexto (desde el wizard), aseguramos el vínculo con ese empleado.
        if ($this->empleadoContextoId) {
            $yaVinculado = collect($this->vinculos)->contains(
                fn ($v) => (int) $v['empleado_id'] === (int) $this->empleadoContextoId
            );

            if ($yaVinculado) {
                $this->alert('warning', "{$persona->nombres} ya está vinculado a este empleado.");
            } else {
                $empleado = PlanEmpleado::find($this->empleadoContextoId);
                $this->vinculos[] = [
                    'id' => null,
                    'empleado_id' => $empleado->id,
                    'empleado_nombre' => $empleado->nombreCompleto,
                    'rol' => $this->rolContexto ?? 'padre',
                    'mes_vigencia' => now()->month,
                    'anio_vigencia' => now()->year,
                ];
                $this->alert('info', "{$persona->nombres} ya estaba registrado. Se agregó el vínculo con este empleado.");
            }
        }
    }

    private function nuevaPersona(string $documento): void
    {
        $this->resetPersona();
        $this->documento = $documento;

        if ($this->empleadoContextoId) {
            $empleado = PlanEmpleado::find($this->empleadoContextoId);
            $this->vinculos[] = [
                'id' => null,
                'empleado_id' => $empleado->id,
                'empleado_nombre' => $empleado->nombreCompleto,
                'rol' => $this->rolContexto ?? 'padre',
                'mes_vigencia' => now()->month,
                'anio_vigencia' => now()->year,
            ];
        }
    }

    /** Agregar otro empleado vinculado sin salir del form (ej. la madre, tras registrar al padre). */
    public function agregarVinculoDesdeBuscador()
    {
        if (!$this->nuevoVinculoEmpleadoId) {
            $this->alert('error', 'Seleccione un empleado.');
            return;
        }

        $yaExiste = collect($this->vinculos)->contains(
            fn ($v) => (int) $v['empleado_id'] === (int) $this->nuevoVinculoEmpleadoId
        );

        if ($yaExiste) {
            $this->alert('error', 'Ese empleado ya está vinculado a este registro.');
            return;
        }

        $empleado = PlanEmpleado::findOrFail($this->nuevoVinculoEmpleadoId);

        $this->vinculos[] = [
            'id' => null,
            'empleado_id' => $empleado->id,
            'empleado_nombre' => $empleado->nombreCompleto,
            'rol' => $this->nuevoVinculoRol,
            'mes_vigencia' => $this->nuevoVinculoMes,
            'anio_vigencia' => $this->nuevoVinculoAnio,
        ];

        $this->reset(['nuevoVinculoEmpleadoId']);
        $this->nuevoVinculoRol = 'padre';
    }

    public function confirmarQuitarVinculo(int $index)
    {
        if (count($this->vinculos) <= 1) {
            $this->alert('error', 'Debe mantener al menos un padre, madre o tutor vinculado.');
            return;
        }

        $fila = $this->vinculos[$index];

        if (empty($fila['id'])) {
            // Aún no existe en BD (se agregó en esta sesión de edición): se quita directo, sin confirmar.
            $this->quitarVinculoLocal($index);
            return;
        }

        $this->confirm("¿Desvincular a {$fila['empleado_nombre']}?", [
            'onConfirmed' => 'quitarVinculoConfirmado',
            'data' => ['index' => $index, 'id' => $fila['id']],
        ]);
    }

    public function quitarVinculoConfirmado(array $data)
    {
        try {
            app(DerechoHabienteService::class)->eliminarVinculo((int) $data['id']);
            $this->quitarVinculoLocal((int) $data['index']);
            $this->alert('success', 'Vínculo eliminado.');
            $this->dispatch('derechoHabienteGuardado');
        } catch (\Throwable $th) {
            $this->alert('error', $th->getMessage());
        }
    }

    private function quitarVinculoLocal(int $index): void
    {
        unset($this->vinculos[$index]);
        $this->vinculos = array_values($this->vinculos);
    }

    protected function rules(): array
    {
        $rules = [
            'nombres' => ['required', 'min:5', 'regex:/^[\pL\s]+$/u'],
            'documento' => [
                'required', 'digits:8',
                Rule::notIn(['00000000', '11111111', '22222222', '33333333', '44444444',
                    '55555555', '66666666', '77777777', '88888888', '99999999', '12345678', '87654321']),
            ],
            'fecha_nacimiento' => ['required', 'date', 'before:today', 'after:1950-01-01'],
            'tipo' => ['required', 'in:hijo,conyuge'],
        ];

        if ($this->tipo === 'hijo') {
            if (!$this->discapacidad_severa && $this->esta_estudiando) {
                $rules['fecha_inicio_estudios'] = ['required', 'date'];
                $rules['fecha_fin_estudios'] = ['nullable', 'date', 'after:fecha_inicio_estudios'];
            }

            foreach ($this->vinculos as $i => $v) {
                $rules["vinculos.$i.mes_vigencia"] = ['required', 'integer', 'between:1,12'];
                $rules["vinculos.$i.anio_vigencia"] = ['required', 'integer', 'between:2000,2100'];
            }
        }

        return $rules;
    }

    public function guardar()
    {
        $this->validate();

        if (count($this->vinculos) < 1) {
            $this->alert('error', 'Debe registrar al menos un vínculo (padre, madre o tutor) antes de guardar.');
            return;
        }

        try {
            $data = [
                    'nombres' => $this->nombres,
                    'documento' => $this->documento,
                    'tipo_documento' => $this->tipo_documento,
                    'fecha_nacimiento' => $this->fecha_nacimiento,
                    'tipo' => $this->tipo,
                    'discapacidad_severa' => $this->discapacidad_severa,
                    'percibe_pension_no_contributiva' => $this->percibe_pension_no_contributiva,
                    'fecha_inicio_estudios' => $this->tipo === 'hijo' && !$this->discapacidad_severa && $this->esta_estudiando
                        ? $this->fecha_inicio_estudios : null,
                    'fecha_fin_estudios' => $this->tipo === 'hijo' && !$this->discapacidad_severa && $this->esta_estudiando
                        ? $this->fecha_fin_estudios : null,
                ];
            
            app(DerechoHabienteService::class)->guardar(
                $this->derechoHabienteId,
                $data,
                array_map(fn ($v) => [...$v, 'activo' => true], $this->vinculos)
            );
            
            $this->alert('success', 'Guardado correctamente.');
            $this->dispatch('derechoHabienteGuardado');
            $this->cerrar();
        } catch (\Throwable $th) {
            $this->alert('error', $th->getMessage());
        }
    }

    private function resetPersona(): void
    {
        $this->derechoHabienteId = null;
        $this->nombres = '';
        $this->documento = '';
        $this->tipo_documento = 'DNI';
        $this->fecha_nacimiento = '';
        $this->tipo = 'hijo';
        $this->discapacidad_severa = false;
        $this->percibe_pension_no_contributiva = false;
        $this->esta_estudiando = false;
        $this->fecha_inicio_estudios = null;
        $this->fecha_fin_estudios = null;
        $this->vinculos = [];
    }

    private function resetFormulario(): void
    {
        $this->resetPersona();
        $this->documentoBusqueda = '';
        $this->empleadoContextoId = null;
        $this->rolContexto = null;
        $this->paso = 'buscar';
        $this->nuevoVinculoEmpleadoId = null;
        $this->nuevoVinculoRol = 'padre';
        $this->nuevoVinculoMes = now()->month;
        $this->nuevoVinculoAnio = now()->year;
    }

    public function cerrar()
    {
        $this->resetFormulario();
        $this->show = false;
    }

    public function render()
    {
        return view('livewire.gestion-planilla.derecho-habiente.derecho-habiente-form-component');
    }
}
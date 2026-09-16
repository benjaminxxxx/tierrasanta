<?php

namespace App\Livewire\Persona;

use App\Traits\HandlesAlerts;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;
use Livewire\Attributes\On;
use App\Models\Persona;
use Illuminate\Support\Str;

class PersonaFormComponent extends Component
{
    use LivewireAlert, HandlesAlerts;
    public bool $mostrarModalPersona = false;
    public int $paso = 1; // 1: Búsqueda, 2: Formulario de Edición/Creación

    // ID de persona seleccionada
    public ?int $personaId = null;
    public int|string|null $personaSeleccionadaId = null;

    // Campos del Formulario (Persona)
    public string $tipo = 'empresa'; // 'individual' o 'empresa'
    public ?string $codigo = null;
    public ?string $tipo_documento = 'RUC';
    public ?string $numero_documento = null;
    public ?string $nombres = null;
    public ?string $apellido_paterno = null;
    public ?string $apellido_materno = null;
    public ?string $razon_social = null;
    public ?string $nombre_mostrar = null;
    public ?string $nombre_legal = null;
    public ?string $telefono_movil = null;
    public ?string $email = null;
    public ?string $direccion = null;
    public ?string $distrito = null;
    public ?string $provincia = null;
    public ?string $departamento = null;

    protected $listeners = ['abrirWizardPersona'];

    public function abrirWizardPersona($personaId = null): void
{
    $this->resetForm();
    $this->mostrarModalPersona = true;

    if ($personaId) {
        $this->cargarPersona((int) $personaId);
        $this->paso = 2; // Ir directo a edición
    } else {
        $this->paso = 1; // Iniciar en búsqueda
    }
}

    // ── Método de búsqueda para x-select-dropdown ──────────────────────
    public function getPersonas($search)
    {
        $query = Persona::orderBy('nombre_mostrar');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('nombre_mostrar', 'like', "%{$search}%")
                    ->orWhere('razon_social', 'like', "%{$search}%")
                    ->orWhere('numero_documento', 'like', "%{$search}%");
            });
        }

        $data = $query->limit(10)->get()
            ->map(fn($p) => [
                'id' => $p->id,
                'name' => "{$p->nombre_mostrar} (" . ($p->numero_documento ?? 'Sin Doc') . ")",
            ])
            ->toArray();
        return $data;

    }

    public function updatedPersonaSeleccionadaId($value): void
    {
        if ($value) {
            $this->cargarPersona((int) $value);
            $this->paso = 2;
        }
    }

    public function cargarPersona(int $id): void
    {
        $persona = Persona::findOrFail($id);

        $this->personaId = $persona->id;
        $this->tipo = $persona->tipo;
        $this->codigo = $persona->codigo;
        $this->tipo_documento = $persona->tipo_documento;
        $this->numero_documento = $persona->numero_documento;
        $this->nombres = $persona->nombres;
        $this->apellido_paterno = $persona->apellido_paterno;
        $this->apellido_materno = $persona->apellido_materno;
        $this->razon_social = $persona->razon_social;
        $this->nombre_mostrar = $persona->nombre_mostrar;
        $this->nombre_legal = $persona->nombre_legal;
        $this->telefono_movil = $persona->telefono_movil;
        $this->email = $persona->email;
        $this->direccion = $persona->direccion;
        $this->distrito = $persona->distrito;
        $this->provincia = $persona->provincia;
        $this->departamento = $persona->departamento;
    }

    public function nuevaPersona(): void
    {
        $this->resetForm();
        $this->paso = 2;
    }

    public function guardarProveedorYSeleccionar(): void
    {
        $rules = [
            'tipo' => 'required|in:individual,empresa',
            'tipo_documento' => 'nullable|string|max:20',
            'numero_documento' => 'nullable|string|max:30',
            'telefono_movil' => 'nullable|string|max:30',
            'email' => 'nullable|email',
        ];

        if ($this->tipo === 'empresa') {
            $rules['razon_social'] = 'required|string|max:255';
        } else {
            $rules['nombres'] = 'required|string|max:255';
            $rules['apellido_paterno'] = 'required|string|max:255';
        }

        $this->validate($rules);

        try {
            // Definir el nombre a mostrar
            $nombreMostrar = $this->tipo === 'empresa'
                ? $this->razon_social
                : trim("{$this->nombres} {$this->apellido_paterno} {$this->apellido_materno}");

            $codigo = $this->codigo ?? ('PER-' . strtoupper(Str::random(6)));

            $persona = Persona::updateOrCreate(
                ['id' => $this->personaId],
                [
                    'codigo' => $codigo,
                    'tipo' => $this->tipo,
                    'tipo_documento' => $this->tipo_documento,
                    'numero_documento' => $this->numero_documento,
                    'nombres' => $this->nombres,
                    'apellido_paterno' => $this->apellido_paterno,
                    'apellido_materno' => $this->apellido_materno,
                    'razon_social' => $this->razon_social,
                    'nombre_mostrar' => $nombreMostrar,
                    'nombre_legal' => $this->tipo === 'empresa' ? $this->razon_social : $nombreMostrar,
                    'telefono_movil' => $this->telefono_movil,
                    'email' => $this->email,
                    'direccion' => $this->direccion,
                    'distrito' => $this->distrito,
                    'provincia' => $this->provincia,
                    'departamento' => $this->departamento,
                    'activo' => true,
                ]
            );

            // Despachar evento retornando el ID para ser capturado por el componente padre (ej. ProveedorModal)
            $this->dispatch('personaSeleccionada', persona: $persona->toArray());

            $this->mostrarModalPersona = false;
            $this->resetForm();
        } catch (\Throwable $th) {
            $this->errorAlert($th);
        }
    }

    public function resetForm(): void
    {
        $this->reset([
            'personaId',
            'personaSeleccionadaId',
            'codigo',
            'numero_documento',
            'nombres',
            'apellido_paterno',
            'apellido_materno',
            'razon_social',
            'nombre_mostrar',
            'nombre_legal',
            'telefono_movil',
            'email',
            'direccion',
            'distrito',
            'provincia',
            'departamento',
        ]);
        $this->tipo = 'empresa';
        $this->tipo_documento = 'RUC';
        $this->paso = 1;
        $this->resetValidation();
    }

    public function render()
    {
        return view('livewire.persona.persona-form-component');
    }
}
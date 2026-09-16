<?php

namespace App\Livewire\GestionProveedor;

use App\Models\Persona;
use App\Models\Proveedor;
use App\Services\InformacionGeneral\ProveedorServicio;
use App\Traits\HandlesAlerts;
use Exception;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;

class ProveedoresFormComponent extends Component
{
    use LivewireAlert, HandlesAlerts;

    public $mostrarFormularioProveedores = false;
    public $proveedorId;

    // Persona ya resuelta (elegida, creada o editada) — solo lectura en esta pantalla.
    public ?int $personaId = null;
    public array $personaResumen = [];

    // Búsqueda de persona existente (modo creación)
    public string $busquedaPersona = '';
    public array $resultadosBusquedaPersona = [];

    // Campos propios de Proveedor
    public $tipoContribuyente;
    public $condicion;
    public $estadoContribuyente;
    public $estadoDomicilio;
    public $fechaInscripcion;
    public $fechaInicioActividades;
    public $ciiu;
    public $actividadComercioExterior;
    public ?string $personaRazonSocial = null;
    public ?string $personaNumeroDocumento = null;

    protected $listeners = [
        'editarProveedor',
        'crearProveedor',
        'personaSeleccionada' => 'alPersonaGuardada'
    ];

    protected function rules()
    {
        return [
            'personaId' => 'required|exists:personas,id',
            'tipoContribuyente' => 'nullable|string',
            'condicion' => 'nullable|string',
            'estadoContribuyente' => 'nullable|string',
            'estadoDomicilio' => 'nullable|string',
            'fechaInscripcion' => 'nullable|date',
            'fechaInicioActividades' => 'nullable|date',
            'ciiu' => 'nullable|string',
            'actividadComercioExterior' => 'nullable|string',
        ];
    }

    protected $messages = [
        'personaId.required' => 'Debe seleccionar o crear una persona/empresa para este proveedor.',
    ];

    public function crearProveedor()
    {
        $this->reset([
            'proveedorId',
            'personaId',
            'personaResumen',
            'busquedaPersona',
            'resultadosBusquedaPersona',
            'tipoContribuyente',
            'condicion',
            'estadoContribuyente',
            'estadoDomicilio',
            'fechaInscripcion',
            'fechaInicioActividades',
            'ciiu',
            'actividadComercioExterior',
        ]);
        $this->resetValidation();
        $this->mostrarFormularioProveedores = true;
    }

    public function editarProveedor($id)
    {
        try {
            $proveedor = Proveedor::with('persona')->find($id);
            if (!$proveedor)
                throw new Exception("El registro ya no existe");


            $this->resetValidation();
            $this->proveedorId = $proveedor->id;
            $this->cargarPersonaResumen($proveedor->persona);

            $this->tipoContribuyente = $proveedor->tipo_contribuyente;
            $this->condicion = $proveedor->condicion;
            $this->estadoContribuyente = $proveedor->estado_contribuyente;
            $this->estadoDomicilio = $proveedor->estado_domicilio;
            $this->fechaInscripcion = optional($proveedor->fecha_inscripcion)->format('Y-m-d');
            $this->fechaInicioActividades = optional($proveedor->fecha_inicio_actividades)->format('Y-m-d');
            $this->ciiu = $proveedor->ciiu;
            $this->actividadComercioExterior = $proveedor->actividad_comercio_exterior;

            $this->mostrarFormularioProveedores = true;
        } catch (\Throwable $th) {
            $this->errorAlert($th);
        }
    }

    // ────────────────────────────────────────────────────────────
    // Búsqueda de persona existente (solo aplica al crear un proveedor nuevo)
    // ────────────────────────────────────────────────────────────
    public function updatedBusquedaPersona(string $valor, PersonaServicio $servicio): void
    {
        $this->resultadosBusquedaPersona = strlen($valor) >= 3
            ? $servicio->buscarActivasPorTexto($valor)->toArray()
            : [];
    }

    public function seleccionarPersona(int $id): void
    {
        $persona = Persona::findOrFail($id);
        $this->cargarPersonaResumen($persona);
        $this->busquedaPersona = '';
        $this->resultadosBusquedaPersona = [];
    }

    public function quitarPersonaSeleccionada(): void
    {
        $this->personaId = null;
        $this->personaResumen = [];
    }

    // ────────────────────────────────────────────────────────────
    // Abrir el formulario universal de Persona (crear nueva o editar la actual)
    // ────────────────────────────────────────────────────────────
    public function abrirCrearPersona(): void
    {
        // Sugiere tipo 'empresa' porque viene del módulo de Proveedores,
        // pero el formulario universal deja cambiarlo si hiciera falta.
        $this->dispatch('crearPersona', sugerencia: ['tipo' => 'empresa', 'tipo_documento' => 'RUC']);
    }

    public function abrirEditarPersona(): void
    {
        if (!$this->personaId)
            return;
        $this->dispatch('editarPersona', id: $this->personaId);
    }

    /**
     * El formulario universal de Persona avisa aquí cuando termina de guardar
     * (ya sea que se abrió para crear una nueva o para editar la ya asignada).
     */
    public function alPersonaGuardada($persona): void
    {
        $personaObj = (object) $persona;

        $this->personaId = $personaObj->id;
        $this->personaRazonSocial = $personaObj->tipo === 'empresa'
            ? $personaObj->razon_social
            : $personaObj->nombre_mostrar;

        $this->personaNumeroDocumento = trim(($personaObj->tipo_documento ?? '') . ' ' . ($personaObj->numero_documento ?? ''));
    }

    public function quitarPersona(): void
    {
        $this->personaId = null;
        $this->personaRazonSocial = null;
        $this->personaNumeroDocumento = null;
    }

    private function cargarPersonaResumen($persona): void
    {
        $this->personaId = $persona->id;
        $this->personaResumen = [
            'nombre' => $persona->tipo === 'empresa' ? $persona->razon_social : $persona->nombre_mostrar,
            'nombre_comercial' => $persona->nombre_mostrar,
            'documento' => trim(($persona->tipo_documento ?? '') . ' ' . ($persona->numero_documento ?? '')),
            'telefono' => $persona->telefono,
            'direccion' => $persona->direccion,
            'distrito' => $persona->distrito,
            'provincia' => $persona->provincia,
            'departamento' => $persona->departamento,
        ];
    }

    public function guardarProveedores(ProveedorServicio $servicio)
    {
        $this->validate();

        try {
            $servicio->guardar([
                'persona_id' => $this->personaId,
                'tipo_contribuyente' => $this->tipoContribuyente,
                'condicion' => $this->condicion,
                'estado_contribuyente' => $this->estadoContribuyente,
                'estado_domicilio' => $this->estadoDomicilio,
                'fecha_inscripcion' => $this->fechaInscripcion,
                'fecha_inicio_actividades' => $this->fechaInicioActividades,
                'ciiu' => $this->ciiu,
                'actividad_comercio_exterior' => $this->actividadComercioExterior,
            ], $this->proveedorId);

            $this->alert('success', $this->proveedorId ? 'Registro actualizado exitosamente.' : 'Registro creado exitosamente.');
            $this->dispatch('ActualizarProveedores');
            $this->mostrarFormularioProveedores = false;
        } catch (\Throwable $e) {
            $this->errorAlert($e);
        }
    }

    public function render()
    {
        return view('livewire.gestion-proveedor.proveedores-form-component');
    }
}
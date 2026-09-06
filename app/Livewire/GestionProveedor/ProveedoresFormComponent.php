<?php

namespace App\Livewire\GestionProveedor;

use App\Models\TiendaComercial;
use Illuminate\Database\QueryException;
use Illuminate\Validation\Rule;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;

class ProveedoresFormComponent extends Component
{
    use LivewireAlert;

    public $mostrarFormularioProveedores = false;
    public $proveedorId;

    public $razonSocial;
    public $nombreComercial;
    public $ruc;
    public $contacto;
    public $tipoContribuyente;
    public $condicion;
    public $estadoContribuyente;
    public $estadoDomicilio;
    public $fechaInscripcion;
    public $fechaInicioActividades;
    public $direccionFiscal;
    public $distrito;
    public $provincia;
    public $departamento;
    public $ciiu;
    public $actividadComercioExterior;

    protected $listeners = ['editarProveedor', 'crearProveedor'];

    protected function rules()
    {
        return [
            'razonSocial' => 'required|string',
            'nombreComercial' => 'nullable|string',
            'ruc' => [
                'nullable',
                'numeric',
                'digits:11',
                Rule::unique('tienda_comercials', 'ruc')->ignore($this->proveedorId),
            ],
            'contacto' => 'nullable|string',
            'tipoContribuyente' => 'nullable|string',
            'condicion' => 'nullable|string',
            'estadoContribuyente' => 'nullable|string',
            'estadoDomicilio' => 'nullable|string',
            'fechaInscripcion' => 'nullable|date',
            'fechaInicioActividades' => 'nullable|date',
            'direccionFiscal' => 'nullable|string',
            'distrito' => 'nullable|string',
            'provincia' => 'nullable|string',
            'departamento' => 'nullable|string',
            'ciiu' => 'nullable|string',
            'actividadComercioExterior' => 'nullable|string',
        ];
    }

    protected $messages = [
        'razonSocial.required' => 'La Razón Social es obligatoria.',
        'ruc.unique' => 'El Ruc ya está en uso.',
        'ruc.digits' => 'El Ruc debe tener exactamente 11 dígitos.',
        'ruc.numeric' => 'El Ruc debe ser numérico.',
    ];

    public function crearProveedor()
    {
        $this->reset([
            'proveedorId', 'razonSocial', 'nombreComercial', 'ruc', 'contacto',
            'tipoContribuyente', 'condicion', 'estadoContribuyente', 'estadoDomicilio',
            'fechaInscripcion', 'fechaInicioActividades', 'direccionFiscal',
            'distrito', 'provincia', 'departamento', 'ciiu', 'actividadComercioExterior',
        ]);
        $this->resetValidation();
        $this->mostrarFormularioProveedores = true;
    }

    public function editarProveedor($id)
    {
        $proveedor = TiendaComercial::find($id);

        if ($proveedor) {
            $this->resetValidation();
            $this->proveedorId = $proveedor->id;
            $this->razonSocial = $proveedor->razon_social;
            $this->nombreComercial = $proveedor->nombre_comercial;
            $this->ruc = $proveedor->ruc;
            $this->contacto = $proveedor->contacto;
            $this->tipoContribuyente = $proveedor->tipo_contribuyente;
            $this->condicion = $proveedor->condicion;
            $this->estadoContribuyente = $proveedor->estado_contribuyente;
            $this->estadoDomicilio = $proveedor->estado_domicilio;
            $this->fechaInscripcion = optional($proveedor->fecha_inscripcion)->format('Y-m-d');
            $this->fechaInicioActividades = optional($proveedor->fecha_inicio_actividades)->format('Y-m-d');
            $this->direccionFiscal = $proveedor->direccion_fiscal;
            $this->distrito = $proveedor->distrito;
            $this->provincia = $proveedor->provincia;
            $this->departamento = $proveedor->departamento;
            $this->ciiu = $proveedor->ciiu;
            $this->actividadComercioExterior = $proveedor->actividad_comercio_exterior;
            $this->mostrarFormularioProveedores = true;
        }
    }

    public function guardarProveedores()
    {
        $this->validate();

        if ($this->ruc && !$this->proveedorId) {
            $eliminado = TiendaComercial::onlyTrashed()->where('ruc', $this->ruc)->first();
            if ($eliminado) {
                $this->alert('warning', "Este RUC pertenece a un proveedor eliminado: \"{$eliminado->razon_social}\". Restáurelo en vez de crear uno nuevo.");
                return;
            }
        }

        try {
            $data = [
                'razon_social' => mb_strtoupper($this->razonSocial),
                'nombre_comercial' => $this->nombreComercial ? mb_strtoupper($this->nombreComercial) : null,
                'ruc' => $this->ruc,
                'contacto' => $this->contacto ? mb_strtoupper($this->contacto) : null,
                'tipo_contribuyente' => $this->tipoContribuyente,
                'condicion' => $this->condicion,
                'estado_contribuyente' => $this->estadoContribuyente,
                'estado_domicilio' => $this->estadoDomicilio,
                'fecha_inscripcion' => $this->fechaInscripcion,
                'fecha_inicio_actividades' => $this->fechaInicioActividades,
                'direccion_fiscal' => $this->direccionFiscal,
                'distrito' => $this->distrito,
                'provincia' => $this->provincia,
                'departamento' => $this->departamento,
                'ciiu' => $this->ciiu,
                'actividad_comercio_exterior' => $this->actividadComercioExterior,
            ];

            if ($this->proveedorId) {
                $proveedor = TiendaComercial::find($this->proveedorId);
                if ($proveedor) {
                    $data['editado_por'] = auth()->id();
                    $proveedor->update($data);
                    $this->alert('success', 'Registro actualizado exitosamente.');
                }
            } else {
                $data['creado_por'] = auth()->id();
                TiendaComercial::create($data);
                $this->alert('success', 'Registro creado exitosamente.');
            }

            $this->dispatch('ActualizarProveedores');
            $this->mostrarFormularioProveedores = false;
        } catch (QueryException $e) {
            $this->alert('error', 'Ocurrió un error inesperado: ' . $e->getMessage());
        }
    }

    public function render()
    {
        return view('livewire.gestion-proveedor.proveedores-form-component');
    }
}
<?php

namespace App\Livewire\Campo;

use App\Models\Campo;
use App\Models\LaborServicio;
use App\Models\ServicioCampo;
use App\Models\ServicioNombre;
use App\Services\Campo\ServicioCampoServicio;
use App\Traits\HandlesAlerts;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;

class ServicioCampoFormComponent extends Component
{
    use LivewireAlert, HandlesAlerts;

    public $mostrarFormulario = false;
    public $servicioId = null;

    public $servicio = '';
    public $unidad = 'hora';
    public $tipoComprobante = 'factura';
    public $numeroComprobante = '';
    public $tipoCosto = 'blanco';
    public $fechaComprobante;
    public $porcentajeIgv = 18;
    public $costoUnitario = null;
    public $subtotal = null;
    public $igv = null;
    public $total = null;

    // [fecha, campo, labor, cantidad, campania => ?array]
    public $detalles = [];

    protected $listeners = ['crearServicioCampo', 'editarServicioCampo'];

    protected function rules()
    {
        return [
            'servicio' => 'required|string|max:255',
            'unidad' => 'required|string|max:50',
            'tipoComprobante' => 'required|in:' . implode(',', array_keys(ServicioCampo::COMPROBANTES)),
            'numeroComprobante' => 'nullable|string|max:50',
            'tipoCosto' => 'required|in:' . implode(',', array_keys(ServicioCampo::TIPOS_COSTO)),
            'fechaComprobante' => 'required|date',
            'porcentajeIgv' => 'required|numeric|min:0|max:100',
            'costoUnitario' => 'required|numeric|gt:0',
            'detalles' => 'required|array|min:1',
            'detalles.*.fecha' => 'required|date',
            'detalles.*.campo' => 'required|exists:campos,nombre',
            'detalles.*.labor' => 'required|string|max:255',
            'detalles.*.cantidad' => 'required|numeric|gt:0',
        ];
    }

    protected $validationAttributes = [
        'fechaComprobante' => 'fecha del comprobante',
        'costoUnitario' => 'costo por unidad',
        'detalles.*.fecha' => 'fecha',
        'detalles.*.campo' => 'campo',
        'detalles.*.labor' => 'labor',
        'detalles.*.cantidad' => 'cantidad',
    ];

    public function crearServicioCampo()
    {
        $this->resetFormulario();
        $this->fechaComprobante = now()->format('Y-m-d');
        $this->detalles = [$this->filaVacia()];
        $this->mostrarFormulario = true;
    }

    public function editarServicioCampo($id, ServicioCampoServicio $servicioCampo)
    {
        try {
            $registro = ServicioCampo::with('detalles')->findOrFail($id);
            $this->resetFormulario();

            $this->servicioId = $registro->id;
            $this->servicio = $registro->servicio;
            $this->unidad = $registro->unidad;
            $this->tipoComprobante = $registro->tipo_comprobante;
            $this->numeroComprobante = $registro->numero_comprobante;
            $this->tipoCosto = $registro->tipo_costo;
            $this->fechaComprobante = $registro->fecha_comprobante->format('Y-m-d');
            $this->porcentajeIgv = (float) $registro->porcentaje_igv;
            $this->costoUnitario = round((float) $registro->costo_unitario, 6);
            $this->subtotal = (float) $registro->subtotal;
            $this->igv = (float) $registro->igv;
            $this->total = (float) $registro->total;

            $this->detalles = $registro->detalles->map(function ($d) use ($servicioCampo) {
                $fecha = $d->fecha->format('Y-m-d');
                return [
                    'fecha' => $fecha,
                    'campo' => $d->campo,
                    'labor' => $d->labor,
                    'cantidad' => (float) $d->cantidad,
                    'campania' => $servicioCampo->resolverCampania($d->campo, $fecha),
                ];
            })->values()->all();

            $this->mostrarFormulario = true;
        } catch (\Throwable $th) {
            $this->errorAlert($th);
        }
    }

    /**
     * Se llama desde Alpine cuando cambia el campo o la fecha de una fila.
     */
    public function resolverCampania($index, ServicioCampoServicio $servicioCampo)
    {
        if (!isset($this->detalles[$index])) {
            return;
        }
        $fila = $this->detalles[$index];
        $this->detalles[$index]['campania'] = $servicioCampo->resolverCampania($fila['campo'] ?? null, $fila['fecha'] ?? null);
    }

    /**
     * Al elegir un servicio ya usado antes, propone su última configuración (solo al crear).
     */
    public function sugerirDesdeServicio()
    {
        if ($this->servicioId || trim($this->servicio) === '') {
            return;
        }

        $ultimo = ServicioCampo::where('servicio', trim($this->servicio))->latest('fecha_comprobante')->first();
        if (!$ultimo) {
            return;
        }

        $this->unidad = $ultimo->unidad;
        $this->tipoComprobante = $ultimo->tipo_comprobante;
        $this->tipoCosto = $ultimo->tipo_costo;
        $this->costoUnitario = round((float) $ultimo->costo_unitario, 6);
        $this->dispatch('servicio-campo-recalcular');
    }

    public function guardar(ServicioCampoServicio $servicioCampo)
    {
        $this->validate();

        try {
            $servicioCampo->guardar([
                'servicio' => $this->servicio,
                'unidad' => $this->unidad,
                'tipo_comprobante' => $this->tipoComprobante,
                'numero_comprobante' => $this->numeroComprobante,
                'tipo_costo' => $this->tipoCosto,
                'fecha_comprobante' => $this->fechaComprobante,
                'porcentaje_igv' => $this->porcentajeIgv,
                'costo_unitario' => $this->costoUnitario,
                'subtotal' => $this->subtotal,
                'total' => $this->total,
                'detalles' => $this->detalles,
            ], $this->servicioId);

            $this->alert('success', $this->servicioId ? 'Servicio actualizado.' : 'Servicio registrado.');
            $this->mostrarFormulario = false;
            $this->dispatch('serviciosCampoActualizados');
        } catch (\Throwable $th) {
            $this->errorAlert($th);
        }
    }

    private function filaVacia(): array
    {
        return ['fecha' => $this->fechaComprobante, 'campo' => '', 'labor' => '', 'cantidad' => '', 'campania' => null];
    }

    private function resetFormulario(): void
    {
        $this->reset([
            'servicioId', 'servicio', 'unidad', 'tipoComprobante', 'numeroComprobante', 'tipoCosto',
            'fechaComprobante', 'porcentajeIgv', 'costoUnitario', 'subtotal', 'igv', 'total', 'detalles',
        ]);
        $this->resetValidation();
    }

    public function render()
    {
        return view('livewire.campo.servicio-campo-form-component', [
            'campos' => Campo::orderBy('orden')->pluck('nombre'),
            'sugerenciasServicios' => ServicioNombre::sugerencias(),
            'sugerenciasLabores' => LaborServicio::sugerencias(),
            'sugerenciasUnidades' => collect(ServicioCampo::UNIDADES_SUGERIDAS)
                ->merge(ServicioCampo::distinct()->pluck('unidad'))
                ->unique()->values(),
        ]);
    }
}

<?php
// app/Livewire/GestionCuadrilla/GestionCuadrillaPagoComponent.php
namespace App\Livewire\GestionCuadrilla;

use App\Models\CuadActividadBono;
use App\Models\CuadRegistroDiario;
use App\Models\Desglose;
use App\Models\DesgloseDetalle;
use App\Services\GestionCuadrilla\DesgloseServicio;
use App\Traits\HandlesAlerts;
use App\Traits\Selectores\ConSelectorMes;
use DB;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;
use Throwable;

class GestionCuadrillaPagoComponent extends Component
{
    use LivewireAlert, HandlesAlerts, ConSelectorMes;

    public array $formDesglose = [
        'codigo_vale' => null,
        'fecha' => null,
        'monto_inicial' => null,
        'saldo_anterior' => 0,
        'entregado_por' => null,
        'recibido_por' => null,
    ];
    public bool $mostrarModalDesglose = false;
    public $desgloses = [];
    public array $desglosesIds = [];
    public $ultimoDesglose;
    public bool $editandoDesglose = false;
    public ?int $desgloseIdEditar = null;
    public array $nro_documento = [];
    protected $listeners = ['pagoRegistrado' => 'relistar', 'eliminarDesglose', 'eliminarDetalleConfirmado'];

    public function mount(): void
    {
        $this->inicializarMesAnio();
        $this->obtenerDesgloses();
    }
    public function relistar($desgloseId)
    {
        $this->obtenerDesgloses();
    }
    public function obtenerDesgloses()
    {
        $this->desgloses = Desglose::with([
            'detalles' => function ($query) {
                $query->orderBy('nro_documento', 'asc');
            }
        ])
            ->whereYear('fecha', $this->anio)
            ->whereMonth('fecha', $this->mes)
            ->orderBy('fecha')
            ->orderBy('id')
            ->get();

        $this->desgloses->each(function ($desglose) {
            $desglose->detalles->each(function ($desgloseDetalle) {
                $this->nro_documento[$desgloseDetalle->id] = $desgloseDetalle->nro_documento;
            });
        });

        $this->desglosesIds = $this->desgloses->pluck('id')->toArray();
        $this->ultimoDesglose = optional($this->desgloses->last())->id ?? null;
    }
    protected function despuesMesAnioModificado($anio, $mes): void
    {
        // el listado se recarga solo (computed property); Alpine resincroniza
        // el estado de expandido/colapsado leyendo la clave del nuevo mes/año
        $this->obtenerDesgloses();
    }

    public function abrirModalDesglose(DesgloseServicio $servicio): void
    {
        $this->editandoDesglose = false;
        $this->desgloseIdEditar = null;

        $this->formDesglose = [
            'codigo_vale' => null,
            'fecha' => now()->format('Y-m-d'),
            'monto_inicial' => null,
            'saldo_anterior' => $servicio->obtenerSaldoAnteriorSugerido(),
            'entregado_por' => null,
            'recibido_por' => null,
        ];
        $this->mostrarModalDesglose = true;
    }

    public function editarDesglose(int $desgloseId): void
    {
        $desglose = Desglose::findOrFail($desgloseId);

        $this->editandoDesglose = true;
        $this->desgloseIdEditar = $desglose->id;

        $this->formDesglose = [
            'codigo_vale' => $desglose->codigo_vale,
            'fecha' => $desglose->fecha->format('Y-m-d'),
            'monto_inicial' => (float) $desglose->monto_inicial,
            'saldo_anterior' => (float) $desglose->saldo_anterior,
            'entregado_por' => $desglose->entregado_por,
            'recibido_por' => $desglose->recibido_por,
        ];
        $this->mostrarModalDesglose = true;
    }

    public function guardarDesglose(DesgloseServicio $servicio): void
    {
        try {
            $datos = $this->validate([
                'formDesglose.codigo_vale' => ['nullable', 'string', 'max:255'],
                'formDesglose.fecha' => ['required', 'date'],
                'formDesglose.monto_inicial' => ['required', 'numeric', 'min:0'],
                'formDesglose.saldo_anterior' => ['required', 'numeric'],
                'formDesglose.entregado_por' => ['nullable', 'string', 'max:255'],
                'formDesglose.recibido_por' => ['nullable', 'string', 'max:255'],
            ])['formDesglose'];

            if ($this->editandoDesglose) {
                $servicio->actualizar(Desglose::findOrFail($this->desgloseIdEditar), $datos);
                $this->successAlert('Desglose actualizado correctamente.');

                $this->dispatch('desglose-creado', id: $this->desgloseIdEditar);
            } else {
                $desglose = $servicio->crear($datos);
                $this->dispatch('desglose-creado', id: $desglose->id);
                $this->successAlert('Desglose registrado correctamente.');
            }

            $this->mostrarModalDesglose = false;
            $this->obtenerDesgloses();
        } catch (Throwable $th) {
            $this->errorAlert($th);
        }
    }
    public function confirmarEliminarDesglose(int $desgloseId): void
    {
        $this->confirm('¿Eliminar este desglose de forma permanente? Se perderán todos sus gastos.', [
            'onConfirmed' => 'eliminarDesglose',
            'data' => ['desgloseId' => $desgloseId],
        ]);
    }

    public function eliminarDesglose(array $data, DesgloseServicio $servicio): void
    {
        try {
            $servicio->eliminarDesglose(Desglose::findOrFail($data['desgloseId']));
            $this->successAlert('Desglose eliminado.');
            $this->obtenerDesgloses();
        } catch (Throwable $th) {
            $this->errorAlert($th);
        }
    }

    public function confirmarEliminarDetalle(int $detalleId): void
    {
        $this->confirm('¿Está seguro de eliminar este ingreso?', [
            'onConfirmed' => 'eliminarDetalleConfirmado',
            'data' => ['desgloseDetalleId' => $detalleId],
        ]);
    }

    public function eliminarDetalleConfirmado(array $data, DesgloseServicio $servicio): void
    {
        try {
            $servicio->eliminarDetalle(DesgloseDetalle::findOrFail($data['desgloseDetalleId']));
            $this->obtenerDesgloses();
            $this->successAlert('El pago fue revertido correctamente.');
        } catch (Throwable $th) {
            $this->errorAlert($th);
        }
    }
    public function guardarNumeracionCuadrillaDesglose(): void
    {
        $items = $this->nro_documento;
        DB::transaction(function () use ($items) {
            foreach ($items as $desgloseId => $item) {
                //dd($desgloseId,$item);
                DesgloseDetalle::where('id', $desgloseId)->update([
                    'nro_documento' => $item ?: null,
                ]);
            }
        });
        $this->obtenerDesgloses();
        $this->successAlert('Numeración de documentos actualizada correctamente.');
    }
    public function render()
    {
        return view('livewire.gestion-cuadrilla.gestion-cuadrilla-pago-component');
    }
}
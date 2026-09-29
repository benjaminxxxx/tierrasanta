<?php

namespace App\Livewire\Campo;

use App\Models\ServicioCampo;
use App\Models\ServicioCampoDetalle;
use App\Services\Campo\ServicioCampoServicio;
use App\Traits\HandlesAlerts;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;
use Livewire\WithPagination;

class ServiciosCampoComponent extends Component
{
    use LivewireAlert, WithPagination, HandlesAlerts;

    public $filtroAnio;
    public $filtroMes = '';
    public $filtroCampo = '';
    public $filtroTexto = '';
    public $mostrarResumenAnual = false;

    protected $listeners = ['serviciosCampoActualizados' => '$refresh', 'eliminacionServicioConfirmada'];

    public function mount()
    {
        $this->filtroAnio = now()->year;
    }

    public function updating($propiedad)
    {
        if (str_starts_with($propiedad, 'filtro')) {
            $this->resetPage();
        }
    }

    public function confirmarEliminacion($id)
    {
        $this->confirm('¿Eliminar este servicio y todos sus campos trabajados?', [
            'onConfirmed' => 'eliminacionServicioConfirmada',
            'data' => ['id' => $id],
        ]);
    }

    public function eliminacionServicioConfirmada($data)
    {
        try {
            app(ServicioCampoServicio::class)->eliminar((int) $data['id']);
            $this->alert('success', 'Servicio eliminado (queda registrado en auditoría).');
        } catch (\Throwable $th) {
            $this->errorAlert($th);
        }
    }

    public function reasignarCampanias(ServicioCampoServicio $servicioCampo)
    {
        try {
            [$vinculados, $pendientes] = $servicioCampo->reasignarCampaniasHuerfanas();
            $mensaje = "{$vinculados} detalle(s) vinculados a su campaña.";
            if ($pendientes) {
                $mensaje .= " {$pendientes} siguen sin campaña: cree la campaña o edite el servicio.";
            }
            $this->infoAlert($mensaje, 'Reasignación de campañas');
        } catch (\Throwable $th) {
            $this->errorAlert($th);
        }
    }

    public function render()
    {
        $filtrarDetalle = function ($q) {
            $q->when($this->filtroAnio, fn($q) => $q->whereYear('fecha', $this->filtroAnio))
                ->when($this->filtroMes, fn($q) => $q->whereMonth('fecha', $this->filtroMes))
                ->when($this->filtroCampo, fn($q) => $q->where('campo', $this->filtroCampo));
        };

        $texto = trim($this->filtroTexto);

        $servicios = ServicioCampo::query()
            ->whereHas('detalles', $filtrarDetalle)
            ->when($texto !== '', function ($q) use ($texto) {
                $q->where(function ($q) use ($texto) {
                    $q->where('servicio', 'like', "%{$texto}%")
                        ->orWhere('numero_comprobante', 'like', "%{$texto}%")
                        ->orWhereHas('detalles', fn($d) => $d->where('labor', 'like', "%{$texto}%"));
                });
            })
            ->with(['detalles' => fn($q) => $q->with('campania')->orderBy('fecha')->orderBy('campo'), 'creador'])
            ->orderByDesc('fecha_comprobante')
            ->orderByDesc('id')
            ->paginate(15);

        $sinCampania = ServicioCampoDetalle::whereNull('campania_id')
            ->with('servicio')
            ->orderBy('fecha')
            ->get();

        return view('livewire.campo.servicios-campo-component', compact('servicios', 'sinCampania'));
    }
}

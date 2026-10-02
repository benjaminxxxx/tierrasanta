<?php

namespace App\Livewire\Planilla\Asistencia;

use App\Constants\Permisos;
use App\Models\Configuracion;
use App\Services\Planilla\Asistencia\PlanillaAsistenciaMensualConsulta;
use App\Services\Planilla\PlanillaEmpleadoServicio;
use App\Traits\Selectores\ConSelectorMes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Session;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Asistencia mensual de la planilla: horas, sueldo pagado y costo para la empresa de cada empleado, día por
 * día (Handsontable de solo lectura). Los filtros, las tarjetas y el cambio de vista se resuelven en el
 * navegador; el servidor solo entrega los datos del mes.
 */
#[Title('Asistencia Mensual')]
class AsistenciaMensualComponent extends Component
{
    use ConSelectorMes;

    const CODIGO_CONFIG_ORDEN = 'orden_planilla_asistencia';
    public $ordenGuardado = [];
    public $mostrandoModalOrden = false;

    private ?array $datos = null;

    public function mount($anio = null, $mes = null)
    {
        // /planilla/asistencia/2026/2 abre ese mes (y lo deja como el mes de trabajo)
        if (ctype_digit((string) $anio) && ctype_digit((string) $mes) && (int) $mes >= 1 && (int) $mes <= 12) {
            Session::put($this->dateSessionKey, ['mes' => (int) $mes, 'anio' => (int) $anio]);
        }
        $this->ordenGuardado = $this->obtenerOrdenGuardado();
        $this->inicializarMesAnio();
    }

    protected function despuesMesAnioModificado(string $anio, string $mes)
    {
        $this->enviarDatos();
    }

    protected function obtenerOrdenGuardado(): array
    {
        $config = Configuracion::where('codigo', self::CODIGO_CONFIG_ORDEN)->first();

        if (!$config || !$config->valor) {
            return [];
        }

        $decodificado = json_decode($config->valor, true);

        return is_array($decodificado) ? $decodificado : [];
    }

    public function guardarOrdenConfiguracion($orden)
    {
        $ordenLimpio = collect($orden)
            ->filter(fn($item) => !empty($item['campo']))
            ->map(fn($item) => [
                'campo' => $item['campo'],
                'direccion' => $item['direccion'] === 'desc' ? 'desc' : 'asc',
            ])
            ->values()
            ->toArray();

        Configuracion::updateOrCreate(
            ['codigo' => self::CODIGO_CONFIG_ORDEN],
            [
                'valor' => json_encode($ordenLimpio),
                'descripcion' => 'Orden de visualización de la planilla de asistencia mensual',
            ]
        );

        $this->ordenGuardado = $ordenLimpio;
        $this->mostrandoModalOrden = false;
        $this->enviarDatos();
    }

    private function datos(): array
    {
        return $this->datos ??= app(PlanillaAsistenciaMensualConsulta::class)->obtener((int) $this->mes, (int) $this->anio, $this->ordenGuardado);
    }

    /** El mes o el orden cambiaron: la tabla se vuelve a armar en el navegador con los datos nuevos. */
    private function enviarDatos(): void
    {
        $this->datos = null;
        $this->dispatch('asistencia-datos', datos: $this->datos());
    }

    public function render()
    {
        $usuario = auth()->user();

        return view('livewire.planilla.asistencia.asistencia-mensual-component', [
            'datos' => $this->datos(),
            'camposOrdenables' => PlanillaEmpleadoServicio::camposOrdenables(),
            'nombreMes' => ucfirst(Carbon::create((int) $this->anio, (int) $this->mes, 1)->locale('es')->translatedFormat('F Y')),
            'enlaces' => [
                'detalle' => $usuario?->can(Permisos::PLANILLA_ACTIVIDAD) ? route('planilla.registro_diario') : null,
                'riego' => $usuario?->can(Permisos::CAMPO_RIEGO_REPORTE) ? route('riego.reporte_diario') : null,
            ],
        ]);
    }
}

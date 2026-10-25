<?php

namespace App\Livewire\Riego;

use App\Services\Riego\Regador\RiegoRegadorConsulta;
use App\Services\Riego\Regador\RiegoRegadorProceso;
use App\Traits\HandlesAlerts;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * Agregar regadores a una fecha. Se abre con la fecha de la página, pero la fecha se puede cambiar aquí mismo sin perder
 * lo elegido (los que no pueden regar en la nueva fecha se quitan y se avisa). Primero los regadores habituales; para
 * días especiales, cualquier planillero agrario con contrato vigente o cuadrillero.
 */
class ReporteDiarioAgregarRegadoresComponent extends Component
{
    use LivewireAlert, HandlesAlerts;

    public bool $abierto = false;
    public ?string $fecha = null;
    public array $habituales = [];
    public array $planilleros = [];
    public array $cuadrilleros = [];
    /** Elegidos: [{id, tipo (empleados|cuadrilleros), nombre}] */
    public array $elegidos = [];
    public string $tipoOtro = 'empleados';
    public $otroSeleccionado = null;
    public ?string $aviso = null;

    #[On('abrirAgregarRegador')]
    public function abrir(string $fecha): void
    {
        $this->reset(['elegidos', 'otroSeleccionado', 'aviso']);
        $this->fecha = $fecha;
        $this->cargar();
        $this->abierto = true;
    }

    /** Cambiar la fecha dentro del modal: se recargan las listas y se conservan los elegidos que siguen pudiendo regar. */
    public function updatedFecha(): void
    {
        if (!$this->fecha) {
            return;
        }
        $this->cargar();
        $validos = collect($this->planilleros)->merge($this->cuadrilleros)->map(fn($t) => $t['tipo'] . ':' . $t['id'])->all();
        $quitados = collect($this->elegidos)->reject(fn($e) => in_array($e['tipo'] . ':' . $e['id'], $validos, true));
        $this->elegidos = collect($this->elegidos)->filter(fn($e) => in_array($e['tipo'] . ':' . $e['id'], $validos, true))->values()->all();
        $this->aviso = $quitados->isNotEmpty()
            ? 'Se quitaron porque no pueden regar ese día (sin contrato agrario vigente o ya registrados): ' . $quitados->pluck('nombre')->implode(', ') . '.'
            : null;
    }

    private function cargar(): void
    {
        $this->planilleros = RiegoRegadorConsulta::planilleros($this->fecha);
        $this->cuadrilleros = RiegoRegadorConsulta::cuadrilleros($this->fecha);
        $this->habituales = RiegoRegadorConsulta::habituales($this->fecha, $this->planilleros, $this->cuadrilleros);
    }

    /** Marca o desmarca un regador (habituales y buscador). */
    public function alternar(int $id, string $tipo): void
    {
        $clave = fn($e) => $e['tipo'] === $tipo && (int) $e['id'] === $id;
        if (collect($this->elegidos)->contains($clave)) {
            $this->elegidos = collect($this->elegidos)->reject($clave)->values()->all();
            return;
        }
        $t = collect($tipo === 'empleados' ? $this->planilleros : $this->cuadrilleros)->firstWhere('id', $id);
        if ($t) {
            $this->elegidos[] = ['id' => $id, 'tipo' => $tipo, 'nombre' => $t['name']];
        }
    }

    /** Buscador de "otro trabajador": al elegirlo se agrega a la lista. */
    public function updatedOtroSeleccionado($valor): void
    {
        if ($valor) {
            if (!collect($this->elegidos)->contains(fn($e) => $e['tipo'] === $this->tipoOtro && (int) $e['id'] === (int) $valor)) {
                $this->alternar((int) $valor, $this->tipoOtro);
            }
            $this->otroSeleccionado = null;
        }
    }

    public function guardar(): void
    {
        try {
            $n = app(RiegoRegadorProceso::class)->agregar($this->fecha, $this->elegidos);
            $this->abierto = false;
            $this->dispatch('nuevosRegadoresHanSidoAgregados', fecha: $this->fecha);
            $this->alert('success', "{$n} regador(es) agregado(s) al " . \Illuminate\Support\Carbon::parse($this->fecha)->format('d/m/Y') . '.');
        } catch (\Throwable $th) {
            $this->errorAlert($th);
        }
    }

    public function render()
    {
        return view('livewire.riego.reporte-diario-agregar-regadores-component');
    }
}

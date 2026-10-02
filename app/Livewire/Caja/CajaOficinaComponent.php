<?php

namespace App\Livewire\Caja;

use App\Constants\Permisos;
use App\Models\CajaOficinaMovimiento;
use App\Services\Caja\Cierre\CajaCierreConsulta;
use App\Services\Caja\Movimiento\CajaMovimientoCrud;
use App\Services\Caja\Movimiento\CajaMovimientoReglas;
use App\Services\Caja\Oficina\CajaOficinaConsulta;
use App\Services\Caja\Oficina\CajaOficinaCrud;
use App\Services\Caja\Oficina\CajaOficinaImportador;
use App\Services\Caja\Oficina\CajaOficinaProceso;
use App\Traits\HandlesAlerts;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * Caja de oficina: el dinero físico que pasa por la oficina. Se carga del Excel de caja, se marca lo que
 * no entra realmente (genera su inverso) y se envía a la caja de movimientos. No lleva clasificadores ni
 * saldos por fuente.
 */
#[Title('Caja de oficina')]
class CajaOficinaComponent extends Component
{
    use LivewireAlert, HandlesAlerts, WithFileUploads;

    #[Url] public $anio;
    #[Url] public $mes;
    #[Url] public $buscar = '';
    #[Url] public $estado = '';

    // Formulario
    public bool $modalForm = false;
    public ?int $movimientoId = null;
    public string $fecha = '';
    public $semana = 1;
    public string $tipo = 'EGRESO';
    public bool $es_contable = false;
    public $numero_caja = null;
    public string $condicion = 'NEG';
    public ?string $categoria = null;
    public ?string $codigo = null;
    public ?string $beneficiario = null;
    public ?string $descripcion = null;
    public ?string $tipo_documento = null;
    public ?string $numero_documento = null;
    public ?string $situacion_cheque = null;
    public bool $pagado_en_dolares = false;
    public $importe_usd = null;
    public $tipo_cambio_operacion = null;
    public ?string $importe_texto = null;
    public $tipo_cambio = null;

    // Eliminar
    public bool $modalEliminar = false;
    public ?int $eliminarId = null;
    public string $eliminarResumen = '';
    public string $motivoEliminacion = '';

    // Enviar e importar
    public bool $modalEnviar = false;
    public string $notaEnvio = '';
    public bool $modalEnvios = false;
    public bool $modalImportar = false;
    public $archivoImportar;

    private const CAMPOS_FORM = ['fecha', 'semana', 'tipo', 'es_contable', 'numero_caja', 'condicion', 'categoria', 'codigo', 'beneficiario',
        'descripcion', 'tipo_documento', 'numero_documento', 'situacion_cheque', 'pagado_en_dolares', 'importe_usd',
        'tipo_cambio_operacion', 'importe_texto', 'tipo_cambio'];

    public function mount(): void
    {
        $this->anio = (int) ($this->anio ?: now()->year);
        $this->mes = $this->mes === '0' || $this->mes === 0 ? '' : ($this->mes ?: now()->month);
    }

    public function getPuedeGestionarProperty(): bool
    {
        return auth()->user()?->can(Permisos::CAJA_OFICINA_GESTIONAR) ?? false;
    }

    private function soloGestion(): void
    {
        abort_unless($this->puedeGestionar, 403, 'No tienes permiso para modificar la caja de oficina.');
    }

    private function mesEditable(): bool
    {
        return $this->mes && !app(CajaCierreConsulta::class)->estaCerrado((int) $this->anio, (int) $this->mes);
    }

    // ------------------------------------------------------------------ formulario

    public function nuevo(): void
    {
        $this->soloGestion();
        $this->limpiar();
        $hoy = now();
        $this->fecha = $this->mes && ((int) $this->anio !== $hoy->year || (int) $this->mes !== $hoy->month)
            ? Carbon::create((int) $this->anio, (int) $this->mes, 1)->endOfMonth()->toDateString() : $hoy->toDateString();
        $this->alCambiarFecha();
        $this->modalForm = true;
    }

    public function editar(int $id): void
    {
        $this->soloGestion();
        $this->limpiar();
        $m = CajaOficinaMovimiento::findOrFail($id);
        $this->movimientoId = $m->id;
        $this->fecha = $m->fecha->toDateString();
        $this->semana = $m->semana;
        $this->tipo = (float) $m->importe >= 0 ? 'INGRESO' : 'EGRESO';
        $this->es_contable = $m->es_contable;
        $this->numero_caja = $m->numero_caja;
        $this->condicion = $m->condicion;
        $this->categoria = $m->categoria;
        $this->codigo = $m->codigo;
        $this->beneficiario = $m->beneficiario;
        $this->descripcion = $m->descripcion;
        $this->tipo_documento = $m->tipo_documento;
        $this->numero_documento = $m->numero_documento;
        $this->situacion_cheque = $m->situacion_cheque;
        $this->pagado_en_dolares = $m->importe_usd !== null;
        $this->importe_usd = $m->importe_usd !== null ? abs((float) $m->importe_usd) : null;
        $this->tipo_cambio_operacion = $m->tipo_cambio_operacion !== null ? (float) $m->tipo_cambio_operacion : null;
        $this->importe_texto = $m->importe_detalle ?: number_format(abs((float) $m->importe), 2, '.', '');
        $this->tipo_cambio = $m->tipo_cambio !== null ? (float) $m->tipo_cambio : null;
        $this->modalForm = true;
    }

    public function updatedFecha(): void
    {
        $this->alCambiarFecha();
    }

    public function updatedEsContable(): void
    {
        if ($this->es_contable) {
            $this->condicion = 'BLA';
            $this->numero_caja = null;
        }
    }

    private function alCambiarFecha(): void
    {
        if (!$this->fecha) {
            return;
        }
        try {
            $this->semana = CajaMovimientoReglas::semanaDelMes($this->fecha);
            $this->tipo_cambio = app(CajaMovimientoCrud::class)->tipoCambioDelDia($this->fecha);
            if (!$this->movimientoId && !$this->es_contable) {
                $this->numero_caja = app(CajaOficinaCrud::class)->siguienteNumeroCaja($this->fecha);
            }
        } catch (\Throwable) {
            // fecha a medio escribir
        }
    }

    public function getImporteCalculadoProperty(): ?float
    {
        try {
            if ($this->pagado_en_dolares) {
                return is_numeric($this->importe_usd) && is_numeric($this->tipo_cambio_operacion)
                    ? round((float) $this->importe_usd * (float) $this->tipo_cambio_operacion, 2) : null;
            }
            return $this->importe_texto ? abs(CajaMovimientoReglas::evaluarOperacion($this->importe_texto)) : null;
        } catch (\Throwable) {
            return null;
        }
    }

    public function guardar(): void
    {
        $this->soloGestion();
        try {
            $crud = app(CajaOficinaCrud::class);
            $datos = $this->only(self::CAMPOS_FORM);
            $this->movimientoId ? $crud->actualizar($this->movimientoId, $datos) : $crud->crear($datos);
            $this->modalForm = false;
            $this->alert('success', 'Movimiento guardado. Queda pendiente de envío.');
        } catch (ValidationException $e) {
            throw $e;
        } catch (\InvalidArgumentException $e) {
            $this->addError('importe_texto', $e->getMessage());
        } catch (\Throwable $e) {
            $this->errorAlert($e);
        }
    }

    private function limpiar(): void
    {
        $this->resetErrorBag();
        $this->reset(array_merge(['movimientoId'], array_diff(self::CAMPOS_FORM, ['fecha'])));
    }

    // ------------------------------------------------------------------ inverso y eliminar

    public function generarInverso(int $id): void
    {
        $this->soloGestion();
        try {
            app(CajaOficinaCrud::class)->generarInverso($id);
            $this->alert('success', 'Inverso generado: la fila queda con efecto cero.');
        } catch (\Throwable $e) {
            $this->errorAlert($e);
        }
    }

    public function confirmarEliminar(int $id): void
    {
        $this->soloGestion();
        $m = CajaOficinaMovimiento::with('inverso')->findOrFail($id);
        $this->eliminarId = $id;
        $this->eliminarResumen = $m->fecha->format('d/m/Y') . ' · ' . ($m->beneficiario ?: '—') . ' · ' . $m->descripcion
            . ' · S/ ' . number_format((float) $m->importe, 2) . ($m->inverso ? ' (se elimina también su inverso)' : '');
        $this->motivoEliminacion = '';
        $this->resetErrorBag();
        $this->modalEliminar = true;
    }

    public function eliminar(): void
    {
        $this->soloGestion();
        try {
            app(CajaOficinaCrud::class)->eliminar($this->eliminarId, $this->motivoEliminacion);
            $this->modalEliminar = false;
            $this->alert('success', 'Movimiento eliminado.');
        } catch (\Throwable $e) {
            $this->errorAlert($e);
        }
    }

    // ------------------------------------------------------------------ enviar e importar

    public function enviar(): void
    {
        $this->soloGestion();
        try {
            $envio = app(CajaOficinaProceso::class)->enviar($this->notaEnvio);
            $this->reset(['modalEnviar', 'notaEnvio']);
            $this->successAlert("Envío #{$envio->id} con {$envio->cambios} cambio(s). Queda pendiente hasta que se anexe en la caja de movimientos.");
        } catch (\Throwable $e) {
            $this->errorAlert($e);
        }
    }

    public function importar(): void
    {
        $this->soloGestion();
        $this->validate(['archivoImportar' => 'required|file|extensions:xlsx,xlsm|max:102400'], [
            'archivoImportar.required' => 'Selecciona el Excel de caja.',
            'archivoImportar.extensions' => 'El archivo debe ser .xlsx o .xlsm.',
        ]);
        try {
            $r = app(CajaOficinaImportador::class)->importar($this->archivoImportar->getRealPath());
            $this->reset(['modalImportar', 'archivoImportar']);
            $mensaje = "Excel del {$r['desde']} al {$r['hasta']}: se agregaron {$r['movimientos']} movimiento(s); {$r['existentes']} ya estaban."
                . " {$r['vinculadas']} ya están en la caja de movimientos (quedan vinculadas) y {$r['por_enviar']} quedan por enviar.";
            if ($r['solo_sistema']) {
                $mensaje .= " {$r['solo_sistema']} están en el sistema pero no en el Excel: revísalas.";
            }
            if ($r['meses_omitidos']) {
                $mensaje .= ' Meses cerrados, omitidos: ' . implode(', ', $r['meses_omitidos']) . '.';
            }
            $this->successAlert($mensaje . ' Disponible final: S/ ' . number_format($r['saldo_final'], 2) . '.');
        } catch (\Throwable $e) {
            $this->errorAlert($e);
        }
    }

    public function render()
    {
        $consulta = app(CajaOficinaConsulta::class);

        return view('livewire.caja.caja-oficina-component', [
            'datos' => $consulta->listar(['anio' => (int) $this->anio, 'mes' => $this->mes ? (int) $this->mes : null, 'buscar' => $this->buscar, 'estado' => $this->estado]),
            'anios' => CajaOficinaMovimiento::selectRaw('DISTINCT YEAR(fecha) as anio')->orderByDesc('anio')->pluck('anio')->all() ?: [now()->year],
            'porEnviar' => app(CajaOficinaProceso::class)->porEnviar(),
            'enviosPendientes' => $consulta->envios(),
            'enviosRecientes' => $this->modalEnvios ? $consulta->envios(false, 15) : [],
            'cuadre' => $this->mes ? $consulta->cuadre((int) $this->anio, (int) $this->mes) : null,
            'cerrado' => $this->mes ? app(CajaCierreConsulta::class)->estaCerrado((int) $this->anio, (int) $this->mes) : false,
            'mesEditable' => $this->mesEditable(),
            'nombreMes' => $this->mes ? Carbon::create((int) $this->anio, (int) $this->mes, 1)->locale('es')->translatedFormat('F Y') : (string) $this->anio,
        ]);
    }
}

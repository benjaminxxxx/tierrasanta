<?php

namespace App\Livewire\Caja;

use App\Constants\Permisos;
use App\Models\CajaMovimiento;
use App\Services\Caja\Movimiento\CajaMovimientoConsulta;
use App\Services\Caja\Movimiento\CajaMovimientoCrud;
use App\Services\Caja\Movimiento\CajaMovimientoReglas;
use App\Traits\HandlesAlerts;
use Illuminate\Validation\ValidationException;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * Modal para registrar, editar o duplicar un movimiento de caja. La tabla nunca se edita directamente.
 */
class CajaMovimientoFormComponent extends Component
{
    use LivewireAlert, HandlesAlerts;

    public bool $mostrar = false;
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
    public $caja_clasificador_id = null;
    public ?string $subgrupo_ng = null;
    public ?string $subgrupo_bl = null;
    public ?string $tipo_documento = null;
    public ?string $numero_documento = null;
    public ?string $situacion_cheque = null;
    public bool $pagado_en_dolares = false;
    public $importe_usd = null;
    public $tipo_cambio_operacion = null;
    public ?string $importe_texto = null;
    public $tipo_cambio = null;

    #[On('cajaNuevoMovimiento')]
    public function nuevo(?string $fecha = null): void
    {
        $this->soloGestion();
        $this->limpiar();
        $this->fecha = $fecha ?: now()->toDateString();
        $this->alCambiarFecha();
        $this->mostrar = true;
    }

    #[On('cajaEditarMovimiento')]
    public function editar(int $id, bool $duplicar = false): void
    {
        $this->soloGestion();
        $this->limpiar();
        $m = CajaMovimiento::findOrFail($id);
        $this->movimientoId = $duplicar ? null : $m->id;
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
        $this->caja_clasificador_id = $m->caja_clasificador_id;
        $this->subgrupo_ng = $m->subgrupo_ng;
        $this->subgrupo_bl = $m->subgrupo_bl;
        $this->tipo_documento = $m->tipo_documento;
        $this->numero_documento = $m->numero_documento;
        $this->situacion_cheque = $m->situacion_cheque;
        $this->pagado_en_dolares = $m->importe_usd !== null;
        $this->importe_usd = $m->importe_usd !== null ? abs((float) $m->importe_usd) : null;
        $this->tipo_cambio_operacion = $m->tipo_cambio_operacion !== null ? (float) $m->tipo_cambio_operacion : null;
        // La operación se conserva tal cual: al guardar se toma su valor absoluto y el signo lo da Ingreso/Egreso
        $this->importe_texto = $m->importe_detalle ?: number_format(abs((float) $m->importe), 2, '.', '');
        $this->tipo_cambio = $m->tipo_cambio !== null ? (float) $m->tipo_cambio : null;
        if ($duplicar) {
            // Copia en la misma fecha, al final del día; el N° de caja se conserva (un pago puede tener varias filas)
            $this->importe_texto = null;
            $this->importe_usd = null;
        }
        $this->mostrar = true;
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
            $this->categoria = $this->categoria ?: 'Contable';
        } elseif (!$this->numero_caja && $this->fecha) {
            $this->numero_caja = app(CajaMovimientoCrud::class)->siguienteNumeroCaja($this->fecha);
        }
    }

    public function updatedTipo(): void
    {
        $this->caja_clasificador_id = null;
    }

    private function alCambiarFecha(): void
    {
        if (!$this->fecha) {
            return;
        }
        try {
            $this->semana = CajaMovimientoReglas::semanaDelMes($this->fecha);
            $crud = app(CajaMovimientoCrud::class);
            $this->tipo_cambio = $crud->tipoCambioDelDia($this->fecha);
            if (!$this->movimientoId && !$this->es_contable) {
                $this->numero_caja = $crud->siguienteNumeroCaja($this->fecha);
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
            $datos = $this->only([
                'fecha', 'semana', 'tipo', 'es_contable', 'numero_caja', 'condicion', 'categoria', 'codigo', 'beneficiario',
                'descripcion', 'caja_clasificador_id', 'subgrupo_ng', 'subgrupo_bl', 'tipo_documento', 'numero_documento',
                'situacion_cheque', 'pagado_en_dolares', 'importe_usd', 'tipo_cambio_operacion', 'importe_texto', 'tipo_cambio',
            ]);
            $crud = app(CajaMovimientoCrud::class);
            $this->movimientoId ? $crud->actualizar($this->movimientoId, $datos) : $crud->crear($datos);

            $this->mostrar = false;
            $this->alert('success', 'Movimiento guardado.');
            $this->dispatch('cajaMovimientoGuardado');
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
        $this->reset(['movimientoId', 'semana', 'tipo', 'es_contable', 'numero_caja', 'condicion', 'categoria', 'codigo', 'beneficiario',
            'descripcion', 'caja_clasificador_id', 'subgrupo_ng', 'subgrupo_bl', 'tipo_documento', 'numero_documento',
            'situacion_cheque', 'pagado_en_dolares', 'importe_usd', 'tipo_cambio_operacion', 'importe_texto', 'tipo_cambio']);
    }

    private function soloGestion(): void
    {
        abort_unless(auth()->user()?->can(Permisos::CAJA_MOVIMIENTO_GESTIONAR), 403, 'No tienes permiso para modificar la caja.');
    }

    public function render()
    {
        return view('livewire.caja.caja-movimiento-form-component', [
            'opciones' => $this->mostrar ? app(CajaMovimientoConsulta::class)->opciones() : null,
        ]);
    }
}

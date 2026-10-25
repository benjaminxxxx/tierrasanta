<?php

namespace App\Livewire\Planilla;

use App\Constants\Permisos;
use App\Exceptions\ParametroAfectaPlanillas;
use App\Services\Planilla\Parametro\PlanillaParametroConsulta;
use App\Services\Planilla\Parametro\PlanillaParametroProceso;
use App\Services\Planilla\Parametro\PlanillaParametroReglas;
use App\Traits\HandlesAlerts;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * Historial de un parámetro de planilla y alta / corrección de valores (con motivo). Se abre con el evento
 * abrirParametro (codigo, y opcionalmente mes/anio para proponer ese mes). Si el cambio toca meses con planilla
 * generada, muestra cuáles y qué cambia, y solo guarda (y regenera esas planillas) si el usuario confirma.
 */
class PlanillaParametroModalComponent extends Component
{
    use LivewireAlert, HandlesAlerts;

    public bool $abierto = false;
    public string $codigo = '';
    public array $info = [];
    public array $historial = [];

    // Formulario
    public ?int $editId = null;
    public $valor = null;
    public ?string $desde = null;
    public ?string $motivo = null;

    // Confirmación de planillas afectadas
    public ?array $pendiente = null;
    public array $afectados = [];

    #[On('abrirParametro')]
    public function abrir(string $codigo, ?int $mes = null, ?int $anio = null): void
    {
        $this->codigo = $codigo;
        $this->info = [
            'nombre' => PlanillaParametroReglas::nombre($codigo),
            'unidad' => PlanillaParametroReglas::unidad($codigo),
            'ayuda' => PlanillaParametroReglas::CATALOGO[$codigo][3] ?? 'El sistema no usa este valor.',
            'planillas' => array_map(fn($p) => PlanillaParametroReglas::PLANILLAS[$p], PlanillaParametroReglas::CATALOGO[$codigo][2] ?? []),
        ];
        $this->limpiar();
        $this->desde = $mes && $anio ? sprintf('%04d-%02d', $anio, $mes) : now()->format('Y-m');
        $this->cargar();
        $this->abierto = true;
    }

    public function cargar(): void
    {
        PlanillaParametroConsulta::olvidar();
        $this->historial = PlanillaParametroConsulta::historial($this->codigo);
    }

    public function editar(int $id): void
    {
        $fila = collect($this->historial)->firstWhere('id', $id);
        if (!$fila) {
            return;
        }
        $this->editId = $id;
        $this->valor = $fila['valor'];
        $this->desde = substr($fila['desde'], 0, 7);
        $this->motivo = $fila['motivo'];
        $this->resetErrorBag();
    }

    public function limpiar(): void
    {
        $this->reset(['editId', 'valor', 'motivo', 'pendiente', 'afectados']);
        $this->resetErrorBag();
    }

    public function guardar(): void
    {
        $this->validate(['valor' => 'required|numeric|min:0', 'desde' => 'required|date_format:Y-m', 'motivo' => 'nullable|string|max:255'],
            [], ['valor' => 'valor', 'desde' => 'vigente desde', 'motivo' => 'motivo']);
        $this->ejecutar(['accion' => 'guardar', 'id' => $this->editId, 'valor' => $this->valor, 'desde' => $this->desde, 'motivo' => $this->motivo]);
    }

    public function eliminar(int $id): void
    {
        $this->ejecutar(['accion' => 'eliminar', 'id' => $id]);
    }

    /** "Sí, guardar y regenerar": repite el cambio pendiente con confirmación. */
    public function confirmar(): void
    {
        if ($this->pendiente) {
            $this->ejecutar($this->pendiente, true);
        }
    }

    public function cancelarConfirmacion(): void
    {
        $this->reset(['pendiente', 'afectados']);
    }

    private function ejecutar(array $cambio, bool $confirmado = false): void
    {
        try {
            $this->authorize(Permisos::PLANILLA_PARAMETRO_GESTIONAR);
            $proceso = app(PlanillaParametroProceso::class);
            if ($cambio['accion'] === 'eliminar') {
                $proceso->eliminar($cambio['id'], $confirmado);
            } elseif ($cambio['id']) {
                $proceso->editar($cambio['id'], $cambio['valor'], $cambio['desde'], $cambio['motivo'], $confirmado);
            } else {
                $proceso->agregar($this->codigo, $cambio['valor'], $cambio['desde'], $cambio['motivo'], $confirmado);
            }
            $this->limpiar();
            $this->desde = $cambio['desde'] ?? $this->desde;
            $this->cargar();
            $this->dispatch('parametroGuardado');
            $this->avisarResultado($proceso->regenerados, $cambio['accion'] === 'eliminar' ? 'Valor eliminado.' : 'Valor guardado.');
        } catch (ParametroAfectaPlanillas $e) {
            // No se guardó: se muestra qué meses cambian y se espera la confirmación
            $this->pendiente = $cambio;
            $this->afectados = $e->meses;
        } catch (\Throwable $e) {
            $this->errorAlert($e);
        }
    }

    private function avisarResultado(array $regenerados, string $texto): void
    {
        if (!$regenerados) {
            $this->alert('success', $texto);
            return;
        }
        $html = '<p>' . e($texto) . ' Planillas regeneradas:</p><ul style="text-align:left;margin-top:6px">';
        $conError = false;
        foreach ($regenerados as $r) {
            $conError = $conError || !$r['ok'];
            $html .= '<li>' . ($r['ok'] ? '✔' : '✖') . ' ' . e($r['periodo']) . ' · ' . e(PlanillaParametroReglas::PLANILLAS[$r['planilla']] ?? $r['planilla'])
                . ': ' . e($r['detalle']) . '</li>';
        }
        $this->alert($conError ? 'warning' : 'success', $conError ? 'Guardado; alguna planilla no se pudo regenerar' : 'Guardado y regenerado',
            ['html' => $html . '</ul>', 'position' => 'center', 'toast' => false, 'timer' => null, 'showConfirmButton' => true]);
    }

    public function render()
    {
        return view('livewire.planilla.planilla-parametro-modal-component');
    }
}

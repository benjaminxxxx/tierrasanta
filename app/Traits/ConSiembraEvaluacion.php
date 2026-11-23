<?php

namespace App\Traits;

use App\Models\CampoCampania;
use App\Services\Evaluacion\Siembra\EvaluacionSiembraConsulta;
use App\Services\Evaluacion\Siembra\EvaluacionSiembraProceso;

/**
 * Elegir qué siembra se evalúa (formularios de población y brotes), agregar otra sin salir de la evaluación y corregir
 * una evaluación puesta en la siembra equivocada. Vista: livewire.evaluacion.partials.selector-siembra.
 */
trait ConSiembraEvaluacion
{
    public array $siembras = [];
    public ?int $siembraId = null;
    public bool $campaniaCerrada = false;
    public bool $agregandoSiembra = false;
    public ?string $nuevaSiembraFecha = null;
    public ?string $nuevaSiembraMotivo = null;
    /** La fecha ya existe como plantación: mensaje y fecha que la persona debe confirmar */
    public ?string $siembraExistenteMensaje = null;
    public ?string $siembraExistenteFecha = null;
    /** Sin tipo: el select manda '' cuando no hay nada elegido */
    public $siembraDestino = null;

    /** La siembra elegida cambió: el componente carga lo de esa siembra. */
    abstract protected function alCambiarSiembra(): void;

    abstract protected function campaniaDeSiembras(): ?CampoCampania;

    abstract protected function puedeAgregarSiembra(): bool;

    abstract protected function puedeCorregirSiembra(): bool;

    protected function cargarSiembras(CampoCampania $c, ?int $elegida = null): void
    {
        app(EvaluacionSiembraProceso::class)->vincularCampania($c);
        $this->campaniaCerrada = (bool) $c->fecha_fin;
        $this->siembras = app(EvaluacionSiembraConsulta::class)->siembras($c);
        $ids = array_column($this->siembras, 'id');
        // Con varias siembras se pregunta cuál se evalúa; con una, es esa
        $this->siembraId = in_array($elegida, $ids, true) ? $elegida : (count($ids) === 1 ? $ids[0] : null);
        $this->reset(['agregandoSiembra', 'nuevaSiembraFecha', 'nuevaSiembraMotivo', 'siembraDestino', 'siembraExistenteMensaje', 'siembraExistenteFecha']);
    }

    protected function limpiarSiembras(): void
    {
        $this->reset(['siembras', 'siembraId', 'campaniaCerrada', 'agregandoSiembra', 'nuevaSiembraFecha', 'nuevaSiembraMotivo', 'siembraDestino',
            'siembraExistenteMensaje', 'siembraExistenteFecha']);
    }

    protected function siembraActual(): ?array
    {
        return collect($this->siembras)->firstWhere('id', $this->siembraId);
    }

    public function elegirSiembra(int $id): void
    {
        if (!in_array($id, array_column($this->siembras, 'id'), true) || $id === $this->siembraId) {
            return;
        }
        $this->siembraId = $id;
        $this->siembraDestino = null;
        $this->resetErrorBag();
        $this->alCambiarSiembra();
    }

    public function abrirAgregarSiembra(): void
    {
        abort_unless($this->puedeAgregarSiembra(), 403);
        $this->agregandoSiembra = true;
    }

    public function cancelarAgregarSiembra(): void
    {
        $this->reset(['agregandoSiembra', 'nuevaSiembraFecha', 'nuevaSiembraMotivo', 'siembraExistenteMensaje', 'siembraExistenteFecha']);
    }

    public function noEsSiembraExistente(): void
    {
        $this->reset(['siembraExistenteMensaje', 'siembraExistenteFecha']);
    }

    /** $unir: la persona confirmó que la plantación ya registrada en esa fecha es esta siembra. */
    public function guardarNuevaSiembra(bool $unir = false): void
    {
        abort_unless($this->puedeAgregarSiembra(), 403);
        try {
            $c = $this->campaniaDeSiembras();
            $unir = $unir && $this->siembraExistenteFecha === $this->nuevaSiembraFecha;
            $nueva = app(EvaluacionSiembraProceso::class)->agregarSiembra($c, $this->nuevaSiembraFecha, $this->nuevaSiembraMotivo, $unir);
            $this->cargarSiembras($c->fresh(), $nueva);
            $this->alCambiarSiembra();
            $this->dispatch('campaniaInsertada');
            $this->alert('success', 'Siembra agregada. Ya puedes registrar su evaluación.');
        } catch (\App\Exceptions\SiembraYaRegistrada $e) {
            $this->siembraExistenteMensaje = $e->getMessage();
            $this->siembraExistenteFecha = $e->fecha;
        } catch (\Throwable $e) {
            $this->errorAlert($e);
        }
    }

    /** Corrige la siembra de la evaluación abierta y sigue en ella. */
    public function corregirSiembra(string $tipo, int $evaluacionId): void
    {
        abort_unless($this->puedeCorregirSiembra() && in_array($tipo, ['poblacion', 'brotes'], true), 403);
        try {
            if (!$this->siembraDestino) {
                throw new \App\Exceptions\ErrorControlado('Elige a qué siembra pasa esta evaluación.');
            }
            $destino = (int) $this->siembraDestino;
            app(EvaluacionSiembraProceso::class)->mover($tipo, $evaluacionId, $destino);
            $this->cargarSiembras($this->campaniaDeSiembras(), $destino);
            $this->alCambiarSiembra();
            $this->dispatch($tipo === 'poblacion' ? 'poblacionPlantasRegistrado' : 'brotesPorPisoRegistrado');
            $this->alert('success', 'Evaluación pasada a la otra siembra.');
        } catch (\Throwable $e) {
            $this->errorAlert($e);
        }
    }
}

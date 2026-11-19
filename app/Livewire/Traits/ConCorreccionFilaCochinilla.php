<?php

namespace App\Livewire\Traits;

use App\Services\Cochinilla\Lote\CochinillaLoteProceso;

/**
 * Corregir el n° de lote o eliminar una fila suelta de venteado / filtrado, con motivo y auditoría.
 * Quien lo usa define CLASE_FILA (modelo) y el modal está en livewire/cochinilla/partials/correccion-fila.
 */
trait ConCorreccionFilaCochinilla
{
    public bool $mostrarCorreccion = false;
    public ?int $correccionId = null;
    public string $correccionAccion = 'lote'; // lote | eliminar
    public ?int $correccionLote = null;
    public string $correccionMotivo = '';

    public function abrirCorreccion(int $id, string $accion = 'lote'): void
    {
        $this->resetErrorBag();
        $this->correccionId = $id;
        $this->correccionAccion = $accion === 'eliminar' ? 'eliminar' : 'lote';
        $this->correccionLote = null;
        $this->correccionMotivo = '';
        $this->mostrarCorreccion = true;
    }

    public function aplicarCorreccion(): void
    {
        try {
            $proceso = app(CochinillaLoteProceso::class);
            if ($this->correccionAccion === 'eliminar') {
                self::CLASE_FILA === \App\Models\CochinillaVenteado::class
                    ? $proceso->eliminarVenteado((int) $this->correccionId, $this->correccionMotivo)
                    : $proceso->eliminarFiltrado((int) $this->correccionId, $this->correccionMotivo);
                $mensaje = 'Registro eliminado. Quedó en la auditoría con tu nombre y el motivo.';
            } else {
                $ingreso = $proceso->corregirLoteFila(self::CLASE_FILA, (int) $this->correccionId, $this->correccionLote, $this->correccionMotivo);
                $mensaje = $ingreso
                    ? "Lote corregido: ahora está con el ingreso {$ingreso->lote} ({$ingreso->campo})."
                    : 'Lote corregido. Ese lote aún no tiene ingreso: seguirá en esta lista hasta que se registre.';
            }
            $this->reset(['mostrarCorreccion', 'correccionId', 'correccionLote', 'correccionMotivo']);
            $this->alert('success', $mensaje);
            $this->resetPage();
        } catch (\Throwable $e) {
            $this->errorAlert($e);
        }
    }
}

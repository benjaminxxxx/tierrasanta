<?php

namespace App\Livewire\GestionAlmacen;

use App\Models\AlmacenProductoSalida;
use App\Models\DistribucionCombustible;
use App\Services\Almacen\DistribucionCombustibleServicio;
use App\Traits\HandlesAlerts;
use App\Traits\ListasComunes\HstListas;
use DB;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;

class DistribucionCombustibleFormComponent extends Component
{
    use HstListas, LivewireAlert, HandlesAlerts;

    public array $listaCampos = [];
    public array $listaMaquinarias = [];

    // Modal
    public bool $modalDistribucion = false;
    public ?int $salidaActivaId = null;
    public array $distribucionesActivas = [];
    public array $filasModificadas = [];
    public $respetarSalida = false;
    public $salida;
    public array $filasParaConfirmar = [];
    public array $confirmacionPreview = [];
    public bool $modalConfirmacion = false;
    protected $listeners = ['abrirModalDistribucion'];

    public function mount(): void
    {
        $this->listaCampos = $this->cargarListaHstCampos();
        $this->listaMaquinarias = $this->cargarListaHstMaquinarias();

    }


    // ─── MODAL ──────────────────────────────────────────────────────────────

    public function abrirModalDistribucion(int $salidaId): void
    {
        //dd($salidaId);
        $this->salidaActivaId = $salidaId;
        $this->filasModificadas = [];

        $this->salida = AlmacenProductoSalida::with('distribuciones')->findOrFail($salidaId);

        $this->distribucionesActivas = $this->salida->distribuciones
            ->map(fn($d) => [
                'id' => $d->id,
                'salida_id' => $salidaId,
                'fecha' => $d->fecha,
                'hora_inicio' => $d->hora_inicio,
                'hora_fin' => $d->hora_salida,
                'n_horas' => $d->horas,
                'campo_nombre' => $d->campo,
                'labor_diaria' => $d->actividad,
                'maquinaria_id' => $d->maquinaria_id,
            ])
            ->toArray();
        $this->dispatch('cargarDistribuciones', distribuciones: $this->distribucionesActivas);
        $this->modalDistribucion = true;
    }
    public function guardarDistribuciones(array $data): void
    {
        try {
            $this->confirmacionPreview = DistribucionCombustibleServicio::previsualizar($data, $this->salidaActivaId);
            $this->filasParaConfirmar = $data;
            $this->modalConfirmacion = true;
        } catch (\Exception $e) {
            $this->errorAlert($e);
        }
    }

    public function confirmarGuardarDistribuciones(): void
    {
        try {
            $porIndex = collect($this->confirmacionPreview)->keyBy('fila_index');

            foreach ($this->filasParaConfirmar as $i => &$fila) {
                if ($porIndex->has($i)) {
                    $fila['salida_id'] = $porIndex[$i]['salida_elegida_id'];
                }
            }
            unset($fila);

            $resultados = DistribucionCombustibleServicio::guardarDistribuciones(
                $this->filasParaConfirmar,
                $this->salidaActivaId
            );

            $partes = [];
            if ($resultados['creados'] > 0)
                $partes[] = "{$resultados['creados']} creados";
            if ($resultados['actualizados'] > 0)
                $partes[] = "{$resultados['actualizados']} actualizados";
            if ($resultados['eliminados'] > 0)
                $partes[] = "{$resultados['eliminados']} eliminados";
            $this->alert('success', count($partes) ? implode(', ', $partes) : 'Sin cambios');
            $this->dispatch('distribucionGuardada');

            $this->cerrarModalConfirmacion();
            $this->filasModificadas = [];
            $this->modalDistribucion = false;
            $this->salidaActivaId = null;
            $this->salida = null;
        } catch (\Exception $e) {
            $this->errorAlert($e);
        }
    }
    public function cerrarModalConfirmacion(): void
    {
        $this->modalConfirmacion = false;
        $this->confirmacionPreview = [];
        $this->filasParaConfirmar = [];
    }
    /*
    public function guardarDistribuciones(array $data): void
    {
        try {
            $resultados = DistribucionCombustibleServicio::guardarDistribuciones(
                $data,
                $this->salidaActivaId,
                $this->respetarSalida
            );

            $partes = [];
            if ($resultados['creados'] > 0)
                $partes[] = "{$resultados['creados']} creados";
            if ($resultados['actualizados'] > 0)
                $partes[] = "{$resultados['actualizados']} actualizados";
            if ($resultados['eliminados'] > 0)
                $partes[] = "{$resultados['eliminados']} eliminados";

            $this->alert('success', count($partes) ? implode(', ', $partes) : 'Sin cambios');
            $this->filasModificadas = [];
            $this->modalDistribucion = false;
            $this->salidaActivaId = null;
            $this->salida = null;
        } catch (\Exception $e) {
            $this->errorAlert($e);
        }
    }
*/
    public function render()
    {
        return view('livewire.gestion-almacen.distribucion-combustible-form-component');
    }
}
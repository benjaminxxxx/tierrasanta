<?php

namespace App\Livewire\Almacen\Kardex;

use App\Models\KardexCarga;
use App\Models\KardexCargaDetalle;
use App\Services\Almacen\KardexCarga\AlmacenKardexCargaProceso;
use App\Traits\HandlesAlerts;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * Carga de KARDEX anual (macro con hoja INDICE + una hoja por código de existencia).
 * El avance lo maneja Alpine: pide las filas por procesar y llama a procesar() una por una.
 */
#[Title('Carga de KARDEX anual')]
class KardexCargaComponent extends Component
{
    use WithFileUploads, LivewireAlert, HandlesAlerts;

    public ?int $cargaId = null;

    // Nueva carga
    public bool $mostrarNueva = false;
    public $archivo;
    public int $anio;
    public string $tipo = 'negro';

    // Macro corregido de la carga actual
    public $archivoCorregido;

    public string $filtroEstado = '';

    public function mount(): void
    {
        $this->anio = (int) now()->year;
        $this->cargaId = KardexCarga::latest('id')->value('id');
        $this->mostrarNueva = $this->cargaId === null;
    }

    public function subir(): void
    {
        $this->validate([
            'archivo' => 'required|file|extensions:xlsm,xlsx|max:204800',
            'anio' => 'required|integer|min:2000|max:2100',
            'tipo' => 'required|in:blanco,negro',
        ], [
            'archivo.required' => 'Selecciona el macro de KARDEX.',
            'archivo.extensions' => 'El archivo debe ser .xlsm o .xlsx.',
        ]);

        try {
            $carga = app(AlmacenKardexCargaProceso::class)->subir($this->archivo, $this->anio, $this->tipo);
            $this->cargaId = $carga->id;
            $this->reset(['archivo', 'mostrarNueva']);
            $pendientes = $carga->detalles()->where('estado', KardexCargaDetalle::PENDIENTE)->count();
            $this->successAlert("Macro guardado. Se encontraron {$carga->detalles()->count()} insumos en el índice, {$pendientes} con movimientos por importar.");
        } catch (\Throwable $e) {
            $this->errorAlert($e);
        }
    }

    public function updatedArchivoCorregido(): void
    {
        $this->validate(
            ['archivoCorregido' => 'required|file|extensions:xlsm,xlsx|max:204800'],
            ['archivoCorregido.extensions' => 'El archivo debe ser .xlsm o .xlsx.'],
        );

        try {
            $carga = app(AlmacenKardexCargaProceso::class)->reemplazarArchivo($this->cargaId, $this->archivoCorregido);
            $this->reset('archivoCorregido');
            $this->successAlert("Macro corregido guardado (versión {$carga->version_archivo}). Las filas conservan su estado: "
                . 'procesa las que fallaron o vuelve a procesar cualquiera.');
        } catch (\Throwable $e) {
            $this->errorAlert($e);
        }
    }

    /** Para Alpine: filas de cada botón (verificar, importar_verificados, ejecutar_pendientes, ejecutar_todos). */
    public function idsPara(string $modo): array
    {
        return $this->cargaId ? app(AlmacenKardexCargaProceso::class)->idsPara($this->cargaId, $modo) : [];
    }

    /** Verificación previa de una fila: no escribe nada; junta todas las observaciones de la hoja. */
    public function verificar(int $detalleId): array
    {
        return $this->resultado(app(AlmacenKardexCargaProceso::class)->verificar($detalleId));
    }

    /** Importa (verificando antes) y regenera el kardex de una fila. */
    public function procesar(int $detalleId): array
    {
        return $this->resultado(app(AlmacenKardexCargaProceso::class)->procesar($detalleId));
    }

    private function resultado(KardexCargaDetalle $detalle): array
    {
        return ['estado' => $detalle->estado, 'mensaje' => $detalle->mensaje, 'nombre' => $detalle->nombre];
    }

    public function render()
    {
        $carga = $this->cargaId ? KardexCarga::find($this->cargaId) : null;
        $detalles = $carga
            ? $carga->detalles()->when($this->filtroEstado, fn($q, $e) => $q->where('estado', $e))->get()
            : collect();
        // Sin la relación detalles(): trae orderBy('fila') y MySQL (only_full_group_by) rechaza el GROUP BY
        $conteo = $carga
            ? KardexCargaDetalle::where('kardex_carga_id', $carga->id)->selectRaw('estado, COUNT(*) as n')->groupBy('estado')->pluck('n', 'estado')
            : collect();

        return view('livewire.almacen.kardex.kardex-carga-component', [
            'cargas' => KardexCarga::latest('id')->get(['id', 'nombre_original', 'anio', 'tipo_kardex', 'created_at']),
            'carga' => $carga,
            'detalles' => $detalles,
            'conteo' => $conteo,
        ]);
    }
}

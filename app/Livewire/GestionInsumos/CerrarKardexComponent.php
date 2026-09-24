<?php

namespace App\Livewire\GestionInsumos;

use App\Models\InsKardex;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;

class CerrarKardexComponent extends Component
{
    use LivewireAlert;
    public bool $mostrarModal = false;
    public ?InsKardex $kardex = null;
    public ?InsKardex $kardexSiguiente = null;
    public string $modo = 'cerrar'; // 'cerrar' | 'reabrir'

    // Snapshot de comparación cuando ya existe kardex del año siguiente
    public array $comparacion = [];

    protected $listeners = [
        'cerrarKardexSeleccionado' => 'abrirParaCerrar',
        'reabrirKardexSeleccionado' => 'abrirParaReabrir',
    ];

    public function abrirParaCerrar(int $kardexId): void
    {
        $this->modo = 'cerrar';
        $this->cargarKardex($kardexId);
        $this->mostrarModal = true;
    }

    public function abrirParaReabrir(int $kardexId): void
    {
        $this->modo = 'reabrir';
        $this->cargarKardex($kardexId);
        $this->mostrarModal = true;
    }

    private function cargarKardex(int $kardexId): void
    {
        $this->kardex = InsKardex::with('producto')->findOrFail($kardexId);

        $this->kardexSiguiente = InsKardex::where('producto_id', $this->kardex->producto_id)
            ->where('tipo', $this->kardex->tipo)
            ->where('anio', (int) $this->kardex->anio + 1)
            ->first();

        $this->comparacion = [];

        if ($this->kardexSiguiente) {
            $stockFinalActual = round((float) $this->kardex->stock_final, 3);
            $costoFinalActual = round((float) $this->kardex->costo_final, 4);
            $stockInicialSiguiente = round((float) $this->kardexSiguiente->stock_inicial, 3);
            $costoInicialSiguiente = round((float) $this->kardexSiguiente->costo_total, 4);

            $this->comparacion = [
                'coincide' => abs($stockFinalActual - $stockInicialSiguiente) < 0.001
                    && abs($costoFinalActual - $costoInicialSiguiente) < 0.01,
                'stock_final_actual' => $stockFinalActual,
                'costo_final_actual' => $costoFinalActual,
                'stock_inicial_siguiente' => $stockInicialSiguiente,
                'costo_inicial_siguiente' => $costoInicialSiguiente,
            ];
        }
    }

    /**
     * Crea el kardex del año siguiente usando los saldos finales de este.
     */
    public function aperturarSiguiente(): void
    {
        if (!$this->kardex || $this->kardexSiguiente) {
            return; // ya existe, esto no debe crear uno nuevo — usar actualizarSiguiente()
        }

        $this->kardexSiguiente = InsKardex::create([
            'producto_id' => $this->kardex->producto_id,
            'tipo' => $this->kardex->tipo,
            'anio' => (int) $this->kardex->anio + 1,
            'codigo_existencia' => $this->kardex->codigo_existencia,
            'descripcion' => $this->kardex->descripcion,
            'metodo_valuacion' => $this->kardex->metodo_valuacion,
            'stock_inicial' => $this->kardex->stock_final,
            'costo_total' => $this->kardex->costo_final,
            'estado' => 'activo',
        ]);

        $this->cargarKardex($this->kardex->id); // refresca comparación con el nuevo registro
        $this->alert('success', "Kardex {$this->kardexSiguiente->anio} creado con el saldo final de este periodo.");
    }

    /**
     * Actualiza los saldos iniciales de un kardex del año siguiente que YA existe,
     * usando los saldos finales de este. No crea nada nuevo.
     */
    public function actualizarSiguiente(): void
    {
        if (!$this->kardexSiguiente) {
            return;
        }

        $this->kardexSiguiente->update([
            'stock_inicial' => $this->kardex->stock_final,
            'costo_total' => $this->kardex->costo_final,
        ]);

        $this->cargarKardex($this->kardex->id);
        $this->alert('success', "Saldo inicial del kardex {$this->kardexSiguiente->anio} actualizado.");
    }

    public function confirmarCerrar(): void
    {
        $this->kardex->update(['estado' => 'cerrado']);
        $this->dispatch('kardexEstadoActualizado');
        $this->alert('success', "Kardex {$this->kardex->anio} cerrado correctamente.");
        $this->cargarKardex($this->kardex->id); // refresca el estado mostrado en el modal
        $this->dispatch('kardexCerrado');
    }

    public function confirmarReabrir(): void
    {
        $this->kardex->update(['estado' => 'activo']);
        $this->dispatch('kardexEstadoActualizado');
        $this->alert('success', "Kardex {$this->kardex->anio} reabierto correctamente.");
        $this->cerrarModal();
        $this->dispatch('kardexCerrado');
    }

    public function cerrarModal(): void
    {
        $this->mostrarModal = false;
        $this->kardex = null;
        $this->kardexSiguiente = null;
        $this->comparacion = [];
    }

    public function render()
    {
        return view('livewire.gestion-insumos.cerrar-kardex-component');
    }
}
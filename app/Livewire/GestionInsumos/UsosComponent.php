<?php

namespace App\Livewire\GestionInsumos;

use App\Services\Insumo\InsumoUsoServicio;
use Illuminate\Validation\ValidationException;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;

class UsosComponent extends Component
{
    use LivewireAlert;

    public bool $modalAuditoria = false;
    public array $auditoriaHistorial = [];
    public array $filasModificadas = [];

    public function mount() {}

    public function guardarUsos(array $filas): void
    {
        $guardados = 0;
        $errores   = [];

        foreach ($filas as $fila) {
            try {
                InsumoUsoServicio::guardarUso($fila);
                $guardados++;
            } catch (ValidationException $e) {
                $errores[] = collect($e->errors())->flatten()->first();
            }
        }

        if ($guardados > 0) {
            $this->alert('success', "{$guardados} uso(s) guardado(s) correctamente.");
            $this->dispatch('cargarDataUsos', data: InsumoUsoServicio::getUsos());
        }

        if (!empty($errores)) {
            $this->alert('error', implode(' | ', $errores));
        }
    }

    public function eliminarUso(int $id): void
    {
        InsumoUsoServicio::eliminarUso($id);
        $this->alert('success', 'Uso eliminado correctamente.');
        $this->dispatch('cargarDataUsos', data: InsumoUsoServicio::getUsos());
    }

    public function verAuditoria(int $id): void
    {
        $this->auditoriaHistorial = InsumoUsoServicio::getAuditoria($id);
        $this->modalAuditoria     = true;
    }

    public function render()
    {
        return view('livewire.gestion-insumos.usos-component', [
            'usos' => InsumoUsoServicio::getUsos(),
        ]);
    }
}
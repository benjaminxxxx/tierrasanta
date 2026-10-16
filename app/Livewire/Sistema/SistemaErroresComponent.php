<?php

namespace App\Livewire\Sistema;

use App\Models\ErrorSistema;
use App\Traits\HandlesAlerts;
use Illuminate\Support\Facades\Auth;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithoutUrlPagination;
use Livewire\WithPagination;

/**
 * Sistema → Errores: tickets de errores no controlados que vieron los usuarios (los guarda errorAlert() y el
 * manejador global). Se revisan aquí sin buscar en el log ni pedir capturas.
 */
#[Title('Errores del sistema')]
class SistemaErroresComponent extends Component
{
    use LivewireAlert, HandlesAlerts, WithPagination, WithoutUrlPagination;

    #[Url]
    public string $estado = 'pendiente';
    #[Url]
    public string $buscar = '';

    public bool $mostrarDetalle = false;
    public ?int $errorId = null;
    public string $nota = '';

    public function updatedEstado(): void
    {
        $this->resetPage();
    }

    public function updatedBuscar(): void
    {
        $this->resetPage();
    }

    public function ver(int $id): void
    {
        $this->errorId = $id;
        $this->nota = (string) ErrorSistema::find($id)?->nota;
        $this->mostrarDetalle = true;
    }

    /** Cambia el estado del ticket; con $todos, también el de los demás tickets pendientes del mismo error. */
    public function marcar(string $estado, bool $todos = false): void
    {
        try {
            abort_unless(array_key_exists($estado, ErrorSistema::ESTADOS), 422);
            $error = ErrorSistema::findOrFail($this->errorId);
            $datos = [
                'estado' => $estado,
                'nota' => trim($this->nota) ?: null,
                'revisado_por' => $estado === 'pendiente' ? null : Auth::id(),
                'revisado_en' => $estado === 'pendiente' ? null : now(),
            ];
            $cantidad = $todos
                ? ErrorSistema::where('huella', $error->huella)->where('estado', '!=', 'resuelto')->update($datos)
                : (int) $error->update($datos);
            $this->alert('success', $todos ? "{$cantidad} ticket(s) marcados como " . mb_strtolower(ErrorSistema::ESTADOS[$estado]) . '.' : 'Ticket actualizado.');
            $this->mostrarDetalle = false;
        } catch (\Throwable $e) {
            $this->errorAlert($e);
        }
    }

    public function render()
    {
        $tickets = ErrorSistema::with('usuario:id,name')
            ->when($this->estado !== 'todos', fn($q) => $q->where('estado', $this->estado))
            ->when(trim($this->buscar), function ($q) {
                $t = '%' . trim($this->buscar) . '%';
                $q->where(fn($w) => $w->where('mensaje', 'like', $t)->orWhere('url', 'like', $t)->orWhere('componente', 'like', $t)
                    ->orWhere('accion', 'like', $t)->orWhere('id', ltrim(trim($this->buscar), '#')));
            })
            ->latest('id')
            ->paginate(20);

        // Cuántas veces pasó cada error (misma clase y lugar)
        $veces = ErrorSistema::whereIn('huella', $tickets->pluck('huella')->unique())
            ->selectRaw('huella, COUNT(*) AS n')->groupBy('huella')->pluck('n', 'huella');

        $detalle = $this->mostrarDetalle && $this->errorId ? ErrorSistema::with(['usuario:id,name', 'revisor:id,name'])->find($this->errorId) : null;
        $ocurrencias = $detalle
            ? ErrorSistema::with('usuario:id,name')->where('huella', $detalle->huella)->latest('id')->limit(15)->get(['id', 'created_at', 'user_id', 'url', 'estado'])
            : collect();

        return view('livewire.sistema.sistema-errores-component', [
            'tickets' => $tickets,
            'veces' => $veces,
            'detalle' => $detalle,
            'ocurrencias' => $ocurrencias,
            'pendientes' => ErrorSistema::where('estado', 'pendiente')->count(),
        ]);
    }
}

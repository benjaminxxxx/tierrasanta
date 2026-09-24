{{-- resources/views/livewire/gestion-cuadrilla/partials/modal-desglose.blade.php --}}
<x-dialog-modal wire:model="mostrarModalDesglose">
    <x-slot name="title">Nuevo Desglose</x-slot>

    <x-slot name="content">
        <div class="grid grid-cols-2 gap-4">
            <div>
                <x-label>Referencia Vale Nro.</x-label>
                <x-input wire:model="formDesglose.codigo_vale" />
                <x-input-error for="formDesglose.codigo_vale" />
            </div>
            <div>
                <x-label>Fecha</x-label>
                <x-input type="date" wire:model="formDesglose.fecha" />
                <x-input-error for="formDesglose.fecha" />
            </div>
            <div>
                <x-label>Monto Inicial</x-label>
                <x-input type="number" step="0.01" wire:model="formDesglose.monto_inicial" />
                <x-input-error for="formDesglose.monto_inicial" />
            </div>
            <div>
                <x-label>Saldo Anterior</x-label>
                <x-input type="number" step="0.01" wire:model="formDesglose.saldo_anterior" />
                <x-input-error for="formDesglose.saldo_anterior" />
            </div>
            <div>
                <x-label>Entregado por</x-label>
                <x-input wire:model="formDesglose.entregado_por" />
            </div>
            <div>
                <x-label>Recibido por</x-label>
                <x-input wire:model="formDesglose.recibido_por" />
            </div>
        </div>
    </x-slot>

    <x-slot name="footer">
        <x-button variant="secondary" wire:click="$set('mostrarModalDesglose', false)">Cancelar</x-button>
        <x-button variant="primary" wire:click="guardarDesglose">
            <i class="fa-solid fa-check"></i> Guardar
        </x-button>
    </x-slot>
</x-dialog-modal>
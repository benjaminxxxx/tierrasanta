<x-dialog-modal wire:model.live="mostrarReemplazarCuadrilleroForm">
    <x-slot name="title">
        Reemplazar Cuadrillero
    </x-slot>

    <x-slot name="content">
        @if ($cuadrilleroPorReemplazar)
            <x-input value="{{ $cuadrilleroPorReemplazar->nombres }}" label="Cuadrillero a reemplazar" disabled />
        @endif
        <div class="mt-4">
            <x-label value="Cuadrillero" />
            <x-select-dropdown wire:model="cuadrilleroARemplazarSeleccionado" source="getCuadrillero" />
            {{-- Debug --}}
            <div class="mt-2 p-2 bg-muted rounded text-xs">
                <strong>ID Seleccionado:</strong> {{ $cuadrilleroARemplazarSeleccionado ?? 'Ninguno' }}
            </div>
        </div>
    </x-slot>

    <x-slot name="footer">
        <x-button variant="secondary" wire:click="$set('mostrarReemplazarCuadrilleroForm', false)"
            wire:loading.attr="disabled">
            Cancelar
        </x-button>

        <x-button class="ms-3" wire:click="confirmarReemplazo" wire:loading.attr="disabled">
            Confirmar Reemplazo
        </x-button>
    </x-slot>
</x-dialog-modal>

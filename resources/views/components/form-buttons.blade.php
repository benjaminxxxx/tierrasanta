@props([
    'action' => '', // Método Livewire a ejecutar
    'mostrarFormulario' => 'mostrarFormulario', // Variable a cerrar
    'id' => null, // ID para determinar si es actualización o creación
])

<x-flex class="justify-end w-full">
    <x-secondary-button wire:click="$set('{{ $mostrarFormulario }}', false)" wire:loading.attr="disabled">
        Cerrar
    </x-secondary-button>
    <x-button wire:click="{{ $action }}" wire:loading.attr="disabled">
        @if ($id)
            <i class="fa fa-pencil"></i> Actualizar
        @else
            <i class="fa fa-save"></i> Registrar
        @endif
    </x-button>
</x-flex>

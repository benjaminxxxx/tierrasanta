<div>
    <x-flex class="w-full justify-between my-5">
        <x-h3>
            Fertilización
        </x-h3>
    </x-flex>

    @if ($campania)
        <livewire:historial-consumo-x-campania-component campaniaId="{{ $campania->id }}"
            campaniaUnica="{{ true }}" wire:key="grupo_fertilizante_campania.{{ $campania->id }}" />
    @endif
</div>

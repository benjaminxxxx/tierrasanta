<div>
    <x-flex class="w-full justify-between my-5">
        <x-h3>
            Aplicaciones fitosanitarias
        </x-h3>
    </x-flex>

    @if ($campania)
        <livewire:pesticidas-por-campania-component campaniaId="{{ $campania->id }}"
            campaniaUnica="{{ true }}" wire:key="grupo_aplicaciones_fitosanitarias.{{ $campania->id }}" />
    @endif
</div>

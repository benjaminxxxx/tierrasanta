{{-- El componente --}}
<div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-4">

    <x-card class="p-3">
        <p class="text-xs text-muted-foreground">Campos infestados (temporada)</p>
        <p class="text-2xl font-bold">{{ $camposActivos }}</p>
    </x-card>

    <x-card class="p-3">
        <p class="text-xs text-muted-foreground">KG Madres (temporada)</p>
        <p class="text-2xl font-bold">{{ number_format($kgTemporada, 0) }}</p>
    </x-card>

    <x-card class="p-3 {{ $sinCampania > 0 ? 'border-yellow-500' : '' }}">
        <p class="text-xs text-muted-foreground">Sin campaña asignada</p>
        <p class="text-2xl font-bold {{ $sinCampania > 0 ? 'text-yellow-500' : '' }}">
            {{ $sinCampania }}
        </p>
        @if($sinCampania > 0)
            <p class="text-xs text-yellow-500 mt-1">⚠ Revisar</p>
        @endif
    </x-card>

    <x-card class="p-3 {{ $sinOrigen > 0 ? 'border-yellow-500' : '' }}">
        <p class="text-xs text-muted-foreground">Sin origen campo</p>
        <p class="text-2xl font-bold {{ $sinOrigen > 0 ? 'text-yellow-500' : '' }}">
            {{ $sinOrigen }}
        </p>
        @if($sinOrigen > 0)
            <p class="text-xs text-yellow-500 mt-1">⚠ Revisar</p>
        @endif
    </x-card>

</div>
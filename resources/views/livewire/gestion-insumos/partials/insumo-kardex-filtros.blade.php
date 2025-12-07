<x-flex class="my-4">

    {{-- Filtro por Año --}}
    <x-select wire:model.live="filtroAnio" label="Año">
        <option value="">-- Año --</option>
        @foreach($aniosDisponibles as $anio)
            <option value="{{ $anio }}">{{ $anio }}</option>
        @endforeach
    </x-select>

    {{-- Filtro por Tipo de Kardex --}}
    <x-select wire:model.live="filtroTipo" label="Tipo de Kardex">
        <option value="">-- Tipo --</option>
        <option value="blanco">Blanco</option>
        <option value="negro">Negro</option>
    </x-select>

    {{-- Filtro por Estado --}}
    <x-select wire:model.live="filtroEstado" label="Estado">
        <option value="">-- Estado --</option>
        <option value="activo">Activo</option>
        <option value="cerrado">Cerrado</option>
    </x-select>

    {{-- Filtro por Método de Valuación --}}
    <x-select wire:model.live="filtroMetodo" label="Método de Valuación">
        <option value="">-- Método --</option>
        <option value="promedio">Promedio</option>
        <option value="peps">PEPS</option>
    </x-select>

</x-flex>

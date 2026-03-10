<x-flex class="my-4">

    {{-- Filtro por Año --}}
    <x-select-anios wire:model.live="filtroAnio" label="Año" class="w-auto"/>

    {{-- Filtro por Tipo de Kardex --}}
    <x-select wire:model.live="filtroTipo" label="Tipo de Kardex" class="w-auto">
        <option value="">Seleccionar Tipo</option>
        <option value="blanco">Blanco</option>
        <option value="negro">Negro</option>
    </x-select>

    {{-- Filtro por Estado --}}
    <x-select wire:model.live="filtroEstado" label="Estado" class="w-auto">
        <option value="">Seleccionar Estado</option>
        <option value="activo">Activo</option>
        <option value="cerrado">Cerrado</option>
    </x-select>

    {{-- Filtro por Método de Valuación --}}
    <x-select wire:model.live="filtroMetodo" label="Método de Valuación" class="w-auto">
        <option value="">Seleccionar Método</option>
        <option value="promedio">Promedio</option>
        <option value="peps">PEPS</option>
    </x-select>

</x-flex>

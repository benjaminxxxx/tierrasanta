<div class="my-5 flex flex-wrap items-end gap-3">
    <x-select wire:model.live="filtroAnio" label="Año" class="w-36">
        <option value="">Todos</option>
        @foreach ($aniosDisponibles as $anio)
            <option value="{{ $anio }}">{{ $anio }}</option>
        @endforeach
    </x-select>

    <x-select wire:model.live="filtroTipo" label="Tipo de reporte" class="w-40">
        <option value="">Todos</option>
        <option value="blanco">Blanco</option>
        <option value="negro">Negro</option>
    </x-select>

    <x-select wire:model.live="filtroGrupo" label="Grupo operativo" class="w-48">
        <option value="">Todos</option>
        @foreach ($gruposDisponibles as $grupo)
            <option value="{{ $grupo }}">{{ ucfirst($grupo) }}</option>
        @endforeach
    </x-select>

    @if ($filtroAnio || $filtroTipo || $filtroGrupo)
        <x-button variant="ghost" wire:click="limpiarFiltros">
            <i class="fa fa-times"></i> Limpiar filtros
        </x-button>
    @endif
</div>

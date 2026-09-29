<x-flex class="justify-between w-full mt-4">
    <x-flex>
        <div>
            <x-input-number label="Filtrar por lote" wire:model.live="lote" />
        </div>
        <div>
            <x-select label="Filtrar por año" wire:model.live="anioSeleccionado">
                <option value="">Todos los años</option>
                @foreach ($aniosDisponibles as $anioDisponible)
                    <option value="{{ $anioDisponible }}">{{ $anioDisponible }}</option>
                @endforeach
            </x-select>
        </div>
        @if (!$verLotesSinIngresos)
            <div>
                <x-select-campo label="Filtrar por Campo" wire:model.live="campoSeleccionado" />
            </div>
        @endif

    </x-flex>
    <div>
        <x-toggle-switch :checked="$verLotesSinIngresos" label="Lotes sin ingresos" wire:model.live="verLotesSinIngresos" />

    </div>
</x-flex>

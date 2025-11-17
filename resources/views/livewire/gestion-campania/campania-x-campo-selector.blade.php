<div>
    <x-flex class="justify-between">
        <x-h3>
            Camapañas por Campo
        </x-h3>
        <x-button @click="$wire.dispatch('registroCampania')">
            <i class="fa fa-plus"></i> Registrar Nueva Campaña
        </x-button>
    </x-flex>
    <x-card2 class="mt-4">
        <x-flex class="justify-between">
            <x-flex>
                <x-select-campo wire:model.live="campoSeleccionado" class="max-w-[5rem]" label="Seleccionar Campo" />

                @if (is_array($campanias) && count($campanias) > 0)
                    <x-select wire:model.live="campaniaSeleccionada" label="Seleccionar Campaña">
                        <option value="">Elegir Campaña</option>
                        @foreach ($campanias as $campaniaId => $campaniaNombre)
                            <option value="{{ $campaniaId }}">{{ $campaniaNombre }}</option>
                        @endforeach
                    </x-select>
                    @if ($campaniaSeleccionada)
                        <x-select wire:model.live="opcion" label="Seleccionar Informe">
                            <option value="general">Información General</option>
                            <option value="mano_obra_costos">Mano de Obra - Costos</option>
                        </x-select>
                    @endif

                @endif
            </x-flex>
            @include('livewire.gestion-campania.partials.campania-x-campo-selector-opciones')
        </x-flex>
    </x-card2>

    @if ($campania && $opcion == 'general')
        @include('livewire.gestion-campania.partials.campania-x-campo-selector-informacion-general')
    @endif
    <x-loading wire:loading />
</div>